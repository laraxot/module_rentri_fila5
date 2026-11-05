<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Actions\Concerns\ResolvesFirNumber;
use Modules\Rentri\Datas\RentriFirSignatureHashData;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Exceptions\RentriRequestException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;

/**
 * Firma il FIR digitale nello stato corrente (FirmaProduttore, FirmaAccettazione, ...).
 *
 * Flusso (endpoint verificati):
 * 1. `POST /{numero_fir}/hash` => `token`, `digest_to_sign` (base64), `hash_algorithm` (`SHA256`);
 * 2. firma del digest con la firma remota RENTRI (SignDigestRemotelyAction, PIN dell'utente). Il certificato .p12
 *    di interoperabilita' NON firma i FIR: autentica solo le chiamate;
 * 3. `POST /{numero_fir}/acquisizione-firma` con `certificato`, `token`, firma, `identificativo_utente` => `transazione_id`.
 * Se il passo 2 o 3 fallisce si chiama `POST /{numero_fir}/rollback-firma` per liberare la sessione di firma.
 *
 * Restituisce la trasmissione di acquisizione-firma (submitted: l'esito arriva col polling).
 */
class SignFirAction
{
    use QueueableAction;
    use ResolvesFirNumber;

    public function execute(Fir $fir, RentriCredential $credential, string $pin): RentriTransmission
    {
        $send = app(SendRentriRequestAction::class);
        $paths = config()->array('rentri.payload.signature');

        $hashExchange = $send->exchange($credential, new RentriRequestData(RentriServiceEnum::FORMULARI, 'POST', $this->firPath($fir, 'hash')), $fir);
        $hashResponse = $hashExchange->response;
        if (! $hashResponse instanceof RentriResponseData || ! $hashResponse->successful()) {
            throw RentriRequestException::fromTransmission($hashExchange->transmission, $hashResponse?->problem());
        }
        $hash = RentriFirSignatureHashData::from($hashResponse->body);

        try {
            $signature = app(SignDigestRemotelyAction::class)->execute($credential, $hash->digestToSign, $pin);

            $transmission = $send->exchange($credential, new RentriRequestData(
                service: RentriServiceEnum::FORMULARI,
                method: 'POST',
                path: $this->firPath($fir, 'acquisizione-firma'),
                payload: [
                    $this->key($paths, 'certificate') => $signature->certificate,
                    $this->key($paths, 'token') => $hash->token,
                    $this->key($paths, 'signature') => $signature->signature,
                    $this->key($paths, 'user_identifier') => $credential->subject_identifier,
                ],
            ), $fir)->transmission;
        } catch (\Throwable $exception) {
            $this->rollback($fir, $credential, $hash->token, $paths);

            throw $exception;
        }

        if (! $this->isSuccessful($transmission)) {
            $this->rollback($fir, $credential, $hash->token, $paths);
        }

        return $transmission;
    }

    /**
     * @param  array<array-key, mixed>  $paths
     */
    private function rollback(Fir $fir, RentriCredential $credential, string $token, array $paths): void
    {
        try {
            app(SendRentriRequestAction::class)->execute(
                $credential,
                RentriServiceEnum::FORMULARI->value,
                'POST',
                $this->firPath($fir, 'rollback-firma'),
                [$this->key($paths, 'rollback_token') => $token],
                [],
                $fir,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array<array-key, mixed>  $paths
     */
    private function key(array $paths, string $name): string
    {
        $key = $paths[$name] ?? null;

        return \is_string($key) && $key !== '' ? $key : $name;
    }
}
