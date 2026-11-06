<?php

declare(strict_types=1);

namespace Modules\Rentri\Tests\Feature;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Modules\Rentri\Actions\LoadCredentialKeyPairAction;
use Modules\Rentri\Actions\SendRentriRequestAction;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriCredentialException;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Rentri\Tests\Support\TestCertificate;
use Modules\Rentri\Tests\TestCase;
use PHPUnit\Framework\Assert;
use function Safe\json_decode;
use function Safe\json_encode;

uses(TestCase::class);

beforeEach(function (): void {
    Sleep::fake();
    Http::preventStrayRequests();
});

/**
 * Richieste registrate da Http::fake(), nell'ordine di invio.
 *
 * @return list<Request>
 */
function rentriRecordedRequests(): array
{
    $requests = [];
    foreach (Http::recorded() as $pair) {
        $request = \is_array($pair) ? $pair[0] : null;
        if ($request instanceof Request) {
            $requests[] = $request;
        }
    }

    return $requests;
}

/** Primo valore di un header della richiesta registrata da Http::fake(). */
function rentriHeader(Request $request, string $name): string
{
    $value = $request->header($name)[0] ?? null;
    if (! \is_string($value)) {
        throw new \RuntimeException('Header '.$name.' assente');
    }

    return $value;
}

/**
 * Decodifica e verifica la firma RS256 di un JWT con la chiave pubblica del certificato di test.
 *
 * @return array{header: \stdClass, payload: \stdClass}
 */
function rentriDecodeJwt(string $jwt, TestCertificate $certificate): array
{
    $header = new \stdClass;
    $payload = JWT::decode($jwt, new Key($certificate->publicKeyPem, 'RS256'), $header);
    if (! $header instanceof \stdClass) {
        throw new \RuntimeException('Header JWT non decodificato');
    }

    return ['header' => $header, 'payload' => $payload];
}

describe('LoadCredentialKeyPairAction', function (): void {
    test('estrae chiave e certificato dal .p12 generato al volo', function (): void {
        $certificate = TestCertificate::generate();
        $keyPair = app(LoadCredentialKeyPairAction::class)->execute($certificate->credential());

        Assert::assertSame('RS256', $keyPair->algorithm);
        Assert::assertSame($certificate->x5c(), $keyPair->certificateBase64());
        Assert::assertArrayNotHasKey('privateKeyPem', $keyPair->toArray());
        Assert::assertNotNull($keyPair->validTo);
    });

    test('password errata => RentriCredentialException', function (): void {
        $certificate = TestCertificate::generate();
        $credential = $certificate->credential();
        $credential->certificate_password = 'wrong';
        $credential->save();

        app(LoadCredentialKeyPairAction::class)->execute($credential);
    })->throws(RentriCredentialException::class);
});

describe('SendRentriRequestAction', function (): void {
    test('POST firmata: Authorization JWT RS256 con x5c, Digest corretto, Agid-JWT-Signature con signed_headers', function (): void {
        $certificate = TestCertificate::generate();
        $credential = $certificate->credential();

        Http::fake([
            'demoapi.rentri.gov.it/formulari/v1.0/*' => Http::response(['transazione_id' => 'TX-1'], 202),
        ]);

        $transmission = app(SendRentriRequestAction::class)->execute(
            $credential,
            'formulari',
            'POST',
            '/FIR123/accettazione',
            ['tipoAccettazione' => 'A', 'quantitaAccettata' => ['valore' => 1000.0], 'dataOraArrivo' => '2026-11-04T10:00:00+01:00'],
        );

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($certificate): bool {
            Assert::assertSame('https://demoapi.rentri.gov.it/formulari/v1.0/FIR123/accettazione', $request->url());
            Assert::assertSame('POST', $request->method());

            $body = $request->body();
            $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));
            Assert::assertSame($digest, rentriHeader($request, 'Digest'));
            Assert::assertSame('application/json', rentriHeader($request, 'Content-Type'));

            $authorization = rentriHeader($request, 'Authorization');
            Assert::assertStringStartsWith('Bearer ', $authorization);
            $auth = rentriDecodeJwt(substr($authorization, 7), $certificate);
            Assert::assertSame('RS256', $auth['header']->alg);
            Assert::assertSame([$certificate->x5c()], $auth['header']->x5c);
            Assert::assertSame('01234567890', $auth['payload']->iss);
            Assert::assertSame('rentrigov.demo.api', $auth['payload']->aud);
            Assert::assertIsString($auth['payload']->jti);

            Assert::assertTrue($request->hasHeader('Agid-JWT-Signature'));
            $integrity = rentriDecodeJwt(rentriHeader($request, 'Agid-JWT-Signature'), $certificate);
            Assert::assertSame([$certificate->x5c()], $integrity['header']->x5c);
            $signedHeaders = json_decode((string) json_encode($integrity['payload']->signed_headers), true);
            Assert::assertSame([['digest' => $digest], ['content-type' => 'application/json']], $signedHeaders);
            Assert::assertNotSame($auth['payload']->jti, $integrity['payload']->jti);

            return true;
        });

        Assert::assertSame(TransmissionStatusEnum::SUBMITTED, $transmission->status);
        Assert::assertSame('TX-1', $transmission->transaction_id);
        Assert::assertSame(202, $transmission->response_status);
        Assert::assertSame(1, $transmission->attempts);
        $stored = RentriTransmission::query()->findOrFail($transmission->id);
        Assert::assertSame('formulari', $stored->service);
        Assert::assertSame('POST', $stored->method);
        Assert::assertSame('/FIR123/accettazione', $stored->path);
        Assert::assertSame(TransmissionStatusEnum::SUBMITTED, $stored->status);
        Assert::assertSame('TX-1', $stored->transaction_id);
        Assert::assertSame('A', data_get($stored->request_payload, 'body.tipoAccettazione'));
    });

    test('GET senza body: niente Digest ne\' Agid-JWT-Signature, 2xx senza transazione => succeeded', function (): void {
        $credential = TestCertificate::generate()->credential();
        Http::fake(['demoapi.rentri.gov.it/formulari/v1.0/*' => Http::response(['stato' => 'Accettato'], 200)]);

        $transmission = app(SendRentriRequestAction::class)->execute($credential, 'formulari', 'GET', '/FIR123', [], ['num_iscr_sito' => 'OP1']);

        Http::assertSent(function (Request $request): bool {
            Assert::assertFalse($request->hasHeader('Digest'));
            Assert::assertFalse($request->hasHeader('Agid-JWT-Signature'));
            Assert::assertTrue($request->hasHeader('Authorization'));
            Assert::assertStringContainsString('num_iscr_sito=OP1', $request->url());

            return true;
        });
        Assert::assertSame(TransmissionStatusEnum::SUCCEEDED, $transmission->status);
        Assert::assertNotNull($transmission->completed_at);
    });

    test('segreti oscurati nel payload salvato', function (): void {
        $credential = TestCertificate::generate()->credential();
        Http::fake(['*' => Http::response(['SAD' => 'secret-sad'], 200)]);

        $transmission = app(SendRentriRequestAction::class)->execute($credential, 'sign', 'POST', '/credentials/authorize', ['credentialID' => 'C1', 'PIN' => '123456']);

        $fresh = RentriTransmission::query()->findOrFail($transmission->id);
        Assert::assertSame(['credentialID' => 'C1', 'PIN' => '***'], $fresh->request_payload['body'] ?? null);
        Assert::assertSame(['SAD' => '***'], $fresh->response_body);
        Assert::assertStringNotContainsString('123456', (string) json_encode($fresh->getAttributes()));
        Assert::assertStringNotContainsString(TestCertificate::PASSWORD, (string) json_encode($fresh->getAttributes()));
    });

    test('errore problem+json => failed con codice e dettaglio', function (): void {
        $credential = TestCertificate::generate()->credential();
        Http::fake(['*' => Http::response(['title' => 'Bad Request', 'status' => 400, 'detail' => 'Campo dataOraArrivo obbligatorio'], 400, ['Content-Type' => 'application/problem+json'])]);

        $transmission = app(SendRentriRequestAction::class)->execute($credential, 'formulari', 'POST', '/X/accettazione', ['tipoAccettazione' => 'A']);

        Assert::assertSame(TransmissionStatusEnum::FAILED, $transmission->status);
        Assert::assertSame(400, $transmission->response_status);
        Assert::assertStringContainsString('dataOraArrivo', (string) $transmission->error);
    });

    test('retry sui 503 con JWT rigenerati (jti diverso), poi esito', function (): void {
        config(['rentri.http.retry_times' => 3]);
        $credential = TestCertificate::generate()->credential();
        Http::fake(['*' => Http::sequence()->push([], 503)->push(['ok' => true], 200)]);

        $transmission = app(SendRentriRequestAction::class)->execute($credential, 'formulari', 'GET', '/X');

        Http::assertSentCount(2);
        $tokens = [];
        foreach (rentriRecordedRequests() as $request) {
            $tokens[] = rentriHeader($request, 'Authorization');
        }
        Assert::assertCount(2, array_unique($tokens));
        Assert::assertSame(TransmissionStatusEnum::SUCCEEDED, $transmission->status);
        Assert::assertSame(2, $transmission->attempts);
    });

    test('le trasmissioni non si cancellano', function (): void {
        $transmission = RentriTransmission::factory()->createOne();

        $transmission->delete();
    })->throws(\Modules\Rentri\Exceptions\RentriException::class);
});
