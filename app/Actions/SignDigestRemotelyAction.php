<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Exceptions\RentriException;
use Modules\Rentri\Datas\RemoteSignatureData;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Exceptions\RentriCredentialException;
use Modules\Rentri\Exceptions\RentriRequestException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Spatie\QueueableAction\QueueableAction;

/**
 * Firma un digest (base64) con la firma remota RENTRI (servizio Sign).
 *
 * Verificato: esistono `/credentials/list`, `/credentials/authorize` (con PIN) e `/signatures/signHash`.
 * DA VERIFICARE: base path del servizio, nomi dei campi (allineati alla Cloud Signature Consortium API v1, config
 * `rentri.sign`) e `/credentials/info` per il certificato del firmatario.
 *
 * Il PIN e il SAD sono oscurati nelle trasmissioni registrate.
 */
class SignDigestRemotelyAction
{
    use QueueableAction;

    public function execute(RentriCredential $credential, string $digestBase64, string $pin): RemoteSignatureData
    {
        $credentialId = $credential->remote_signature_credential_id;
        if ($credentialId === null || $credentialId === '') {
            throw RentriCredentialException::remoteSignatureNotConfigured($credential);
        }

        /** @var array<string, string> $keys */
        $keys = config()->array('rentri.sign.request_keys');
        /** @var array<string, string> $responseKeys */
        $responseKeys = config()->array('rentri.sign.response_keys');

        $info = $this->call($credential, 'sign_credentials_info', [
            $keys['credential_id'] => $credentialId,
            $keys['certificates'] => 'single',
        ]);
        $certificate = $info->string($responseKeys['certificate']);

        $authorize = $this->call($credential, 'sign_credentials_authorize', [
            $keys['credential_id'] => $credentialId,
            $keys['num_signatures'] => 1,
            $keys['hashes'] => [$digestBase64],
            $keys['pin'] => $pin,
        ]);
        $sad = $authorize->string($responseKeys['sad']);

        $signed = $this->call($credential, 'sign_signatures_sign_hash', [
            $keys['credential_id'] => $credentialId,
            $keys['sad'] => $sad,
            $keys['hashes'] => [$digestBase64],
            $keys['hash_algo'] => config()->string('rentri.sign.hash_algo'),
            $keys['sign_algo'] => config()->string('rentri.sign.sign_algo'),
        ]);
        $signature = $signed->string($responseKeys['signatures'].'.0');

        if ($certificate === null || $sad === null || $signature === null) {
            throw new RentriRequestException(RentriException::translate('rentri::exceptions.sign.incomplete_response'), $signed->status, $signed->problem());
        }

        return new RemoteSignatureData(signature: $signature, certificate: $certificate);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function call(RentriCredential $credential, string $pathKey, array $payload): RentriResponseData
    {
        $exchange = app(SendRentriRequestAction::class)->exchange($credential, new RentriRequestData(
            service: RentriServiceEnum::SIGN,
            method: 'POST',
            path: config()->string('rentri.paths.'.$pathKey),
            payload: $payload,
        ));

        $response = $exchange->response;
        if (! $response instanceof RentriResponseData || ! $response->successful()) {
            throw $this->failure($exchange->transmission, $response);
        }

        return $response;
    }

    private function failure(RentriTransmission $transmission, ?RentriResponseData $response): RentriRequestException
    {
        return RentriRequestException::fromTransmission($transmission, $response?->problem());
    }
}
