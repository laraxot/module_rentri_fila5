<?php

declare(strict_types=1);

namespace Modules\Rentri\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Modules\Rentri\Actions\PollPendingRentriTransactionsAction;
use Modules\Rentri\Actions\PollRentriTransactionAction;
use Modules\Rentri\Actions\RetryRentriTransmissionAction;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Rentri\Tests\Support\TestCertificate;
use Modules\Rentri\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    Sleep::fake();
    Http::preventStrayRequests();
    rentriPollCredential(reset: true);
});

/**
 * Credenziale con .p12 generato al volo: una per test (azzerata in beforeEach), senza proprieta' dinamiche sul TestCase.
 */
function rentriPollCredential(bool $reset = false): RentriCredential
{
    static $credential = null;
    if ($reset) {
        $credential = null;
    }
    if (! $credential instanceof RentriCredential) {
        $credential = TestCertificate::generate()->credential();
    }

    return $credential;
}

function rentriSubmitted(string $transactionId = 'TX-42'): RentriTransmission
{
    return RentriTransmission::factory()->submitted($transactionId)->createOne([
        'rentri_credential_id' => rentriPollCredential()->id,
    ]);
}

describe('PollRentriTransactionAction', function (): void {
    test('status terminato + result senza errori => succeeded', function (): void {
        $transmission = rentriSubmitted();
        Http::fake([
            'demoapi.rentri.gov.it/formulari/v1.0/TX-42/status' => Http::response(['stato' => 'Eseguita'], 200),
            'demoapi.rentri.gov.it/formulari/v1.0/TX-42/result' => Http::response(['numero_fir' => 'FIR-1'], 200),
        ]);

        $result = app(PollRentriTransactionAction::class)->execute($transmission);

        Assert::assertSame(TransmissionStatusEnum::SUCCEEDED, $result->status);
        Assert::assertNotNull($result->completed_at);
        Assert::assertSame(['numero_fir' => 'FIR-1'], $result->response_body);
        Http::assertSentCount(2);
    });

    test('status 303 See Other => legge il result', function (): void {
        $transmission = rentriSubmitted();
        Http::fake([
            '*/TX-42/status' => Http::response('', 303, ['Location' => '/formulari/v1.0/TX-42/result']),
            '*/TX-42/result' => Http::response([], 200),
        ]);

        Assert::assertSame(TransmissionStatusEnum::SUCCEEDED, app(PollRentriTransactionAction::class)->execute($transmission)->status);
    });

    test('result con errori => failed', function (): void {
        $transmission = rentriSubmitted();
        Http::fake([
            '*/TX-42/status' => Http::response(['stato' => 'Eseguita'], 200),
            '*/TX-42/result' => Http::response(['errori' => [['codice' => 'E01', 'descrizione' => 'Quantita non valida']]], 200),
        ]);

        $result = app(PollRentriTransactionAction::class)->execute($transmission);

        Assert::assertSame(TransmissionStatusEnum::FAILED, $result->status);
        Assert::assertStringContainsString('E01', (string) $result->error);
    });

    test('ancora in elaborazione => resta submitted', function (): void {
        $transmission = rentriSubmitted();
        Http::fake(['*/TX-42/status' => Http::response(['stato' => 'InElaborazione'], 200)]);

        $result = app(PollRentriTransactionAction::class)->execute($transmission);

        Assert::assertSame(TransmissionStatusEnum::SUBMITTED, $result->status);
        Assert::assertSame(2, $result->attempts);
        Http::assertSentCount(1);
    });

    test('status 404 => failed', function (): void {
        $transmission = rentriSubmitted();
        Http::fake(['*/TX-42/status' => Http::response(['title' => 'Not Found', 'status' => 404], 404)]);

        Assert::assertSame(TransmissionStatusEnum::FAILED, app(PollRentriTransactionAction::class)->execute($transmission)->status);
    });

    test('oltre max_attempts => failed', function (): void {
        config(['rentri.polling.max_attempts' => 2]);
        $transmission = rentriSubmitted();
        Http::fake(['*/TX-42/status' => Http::response(['stato' => 'InElaborazione'], 200)]);

        Assert::assertSame(TransmissionStatusEnum::FAILED, app(PollRentriTransactionAction::class)->execute($transmission)->status);
    });
});

describe('PollPendingRentriTransactionsAction', function (): void {
    test('chiude le transazioni in attesa e ignora quelle definitive', function (): void {
        rentriSubmitted('TX-A');
        rentriSubmitted('TX-B');
        RentriTransmission::factory()->createOne(['rentri_credential_id' => rentriPollCredential()->id]);
        Http::fake([
            '*/TX-A/status' => Http::response(['stato' => 'Eseguita'], 200),
            '*/TX-A/result' => Http::response([], 200),
            '*/TX-B/status' => Http::response(['stato' => 'Eseguita'], 200),
            '*/TX-B/result' => Http::response(['errors' => ['x']], 200),
        ]);

        $completed = app(PollPendingRentriTransactionsAction::class)->execute();

        Assert::assertSame(2, $completed);
        Assert::assertSame(0, RentriTransmission::query()->where('status', 'submitted')->count());
        Assert::assertSame(1, RentriTransmission::query()->where('transaction_id', 'TX-B')->where('status', 'failed')->count());
    });
});

describe('RetryRentriTransmissionAction', function (): void {
    test('reinvia una trasmissione fallita sulla stessa riga', function (): void {
        $transmission = RentriTransmission::factory()->failed()->createOne([
            'rentri_credential_id' => rentriPollCredential()->id,
            'method' => 'POST',
            'path' => '/FIR9/accettazione',
            'request_payload' => ['body' => ['tipoAccettazione' => 'A'], 'query' => [], 'meta' => []],
        ]);
        Http::fake(['*/FIR9/accettazione' => Http::response(['transazione_id' => 'TX-R'], 202)]);

        $result = app(RetryRentriTransmissionAction::class)->execute($transmission);

        Assert::assertSame($transmission->id, $result->id);
        Assert::assertSame(TransmissionStatusEnum::SUBMITTED, $result->status);
        Assert::assertSame('TX-R', $result->transaction_id);
        Assert::assertNull($result->error);
    });

    test('non reinvia payload con segreti oscurati', function (): void {
        $transmission = RentriTransmission::factory()->failed()->createOne([
            'rentri_credential_id' => rentriPollCredential()->id,
            'request_payload' => ['body' => ['PIN' => '***'], 'query' => [], 'meta' => []],
        ]);

        Assert::assertFalse(app(RetryRentriTransmissionAction::class)->canRetry($transmission));
        app(RetryRentriTransmissionAction::class)->execute($transmission);
    })->throws(\Modules\Rentri\Exceptions\RentriException::class);
});
