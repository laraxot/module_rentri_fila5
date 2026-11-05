<?php

declare(strict_types=1);

namespace Modules\Rentri\Tests\Unit;

use Modules\Rentri\Actions\BuildDigestHeaderAction;
use Modules\Rentri\Actions\RedactRentriPayloadAction;
use Modules\Rentri\Actions\ResolveRentriCodeAction;
use Modules\Rentri\Datas\RentriProblemData;
use Modules\Rentri\Enums\RentriFirStateEnum;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriMappingException;
use Modules\Rentri\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

describe('BuildDigestHeaderAction', function (): void {
    test('produce SHA-256=<base64> del body (RFC 3230)', function (): void {
        $body = '{"tipoAccettazione":"A"}';

        $digest = app(BuildDigestHeaderAction::class)->execute($body);

        Assert::assertSame('SHA-256='.base64_encode(hash('sha256', $body, true)), $digest);
        Assert::assertStringStartsWith('SHA-256=', $digest);
    });
});

describe('RedactRentriPayloadAction', function (): void {
    test('oscura pin, password e sad a ogni livello, senza distinzione di maiuscole', function (): void {
        $redacted = app(RedactRentriPayloadAction::class)->execute([
            'PIN' => '123456',
            'credentialID' => 'abc',
            'nested' => ['certificate_password' => 'secret', 'SAD' => 'token', 'keep' => 'yes'],
        ]);

        Assert::assertSame('***', $redacted['PIN']);
        Assert::assertSame('abc', $redacted['credentialID']);
        Assert::assertSame(['certificate_password' => '***', 'SAD' => '***', 'keep' => 'yes'], $redacted['nested']);
        Assert::assertTrue(app(RedactRentriPayloadAction::class)->containsRedactedValue($redacted));
        Assert::assertFalse(app(RedactRentriPayloadAction::class)->containsRedactedValue(['a' => 'b']));
    });
});

describe('ResolveRentriCodeAction', function (): void {
    test('tipoAccettazione: full => A', function (): void {
        Assert::assertSame('A', app(ResolveRentriCodeAction::class)->execute('acceptance', 'full'));
    });

    test('valore senza codifica => eccezione', function (): void {
        app(ResolveRentriCodeAction::class)->execute('acceptance', 'unknown');
    })->throws(RentriMappingException::class);

    test('EER senza spazi e asterisco', function (): void {
        Assert::assertSame('160107', app(ResolveRentriCodeAction::class)->ewc('16 01 07*'));
    });
});

describe('TransmissionStatusEnum', function (): void {
    test('transizioni consentite', function (): void {
        Assert::assertTrue(TransmissionStatusEnum::PENDING->canTransitionTo(TransmissionStatusEnum::SUBMITTED));
        Assert::assertTrue(TransmissionStatusEnum::SUBMITTED->canTransitionTo(TransmissionStatusEnum::SUCCEEDED));
        Assert::assertTrue(TransmissionStatusEnum::FAILED->canTransitionTo(TransmissionStatusEnum::PENDING));
        Assert::assertFalse(TransmissionStatusEnum::SUCCEEDED->canTransitionTo(TransmissionStatusEnum::PENDING));
        Assert::assertFalse(TransmissionStatusEnum::SUBMITTED->canTransitionTo(TransmissionStatusEnum::PENDING));
    });
});

describe('RentriFirStateEnum', function (): void {
    test('contiene i 20 stati verificati di StatoFormulario', function (): void {
        Assert::assertCount(20, RentriFirStateEnum::cases());
        Assert::assertSame(RentriFirStateEnum::ACCETTATO, RentriFirStateEnum::from('Accettato'));
        Assert::assertTrue(RentriFirStateEnum::FIRMA_ACCETTAZIONE->awaitsSignature());
        Assert::assertTrue(RentriFirStateEnum::ANNULLATO->isFinal());
        Assert::assertFalse(RentriFirStateEnum::INSERIMENTO_ACCETTAZIONE->isFinal());
    });
});

describe('RentriProblemData', function (): void {
    test('legge application/problem+json', function (): void {
        $problem = RentriProblemData::fromBody(['type' => 'about:blank', 'title' => 'Bad Request', 'status' => 400, 'detail' => 'numero_fir non valido']);

        Assert::assertInstanceOf(RentriProblemData::class, $problem);
        Assert::assertSame('Bad Request — numero_fir non valido', $problem?->summary());
        Assert::assertNull(RentriProblemData::fromBody([]));
    });
});
