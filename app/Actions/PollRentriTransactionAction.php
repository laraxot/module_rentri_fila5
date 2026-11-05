<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Carbon\CarbonImmutable;
use Modules\Rentri\Exceptions\RentriException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Verifica l'esito di una trasmissione asincrona (`submitted` con `transazione_id`).
 *
 * Verificato: `GET /{transazione_id}/status` e `GET /{transazione_id}/result` sul base path del servizio chiamato.
 * DA VERIFICARE: forma della risposta di status. Si considera terminata se risponde 303 See Other (pattern ModI
 * NONBLOCK_PULL_REST) oppure 2xx con `rentri.polling.status_key` in `rentri.polling.done_values`.
 * Esito: result 2xx senza errori (`rentri.polling.result_error_keys`) => succeeded, altrimenti failed.
 * Status 4xx => failed; 5xx o errore di connessione => resta submitted. Oltre `rentri.polling.max_attempts` => failed.
 *
 * Le chiamate di polling non creano nuove trasmissioni: aggiornano quella esistente.
 */
class PollRentriTransactionAction
{
    use QueueableAction;

    public function execute(RentriTransmission $transmission): RentriTransmission
    {
        if ($transmission->status !== TransmissionStatusEnum::SUBMITTED || $transmission->transaction_id === null) {
            return $transmission;
        }

        $credential = $transmission->credential;
        Assert::isInstanceOf($credential, RentriCredential::class);

        $transmission->attempts++;
        $base = '/'.rawurlencode($transmission->transaction_id);

        try {
            $status = $this->get($credential, $transmission, $base.'/status');
        } catch (ConnectionException $exception) {
            return $this->keepWaiting($transmission, $exception->getMessage());
        }

        if (! $status->isSeeOther() && ! $status->successful()) {
            return $status->status >= 500
                ? $this->keepWaiting($transmission, $status->errorSummary())
                : $this->finish($transmission, TransmissionStatusEnum::FAILED, $status, $status->errorSummary());
        }

        if (! $status->isSeeOther() && ! $this->isDone($status)) {
            return $this->keepWaiting($transmission, null);
        }

        try {
            $result = $this->get($credential, $transmission, $base.'/result');
        } catch (ConnectionException $exception) {
            return $this->keepWaiting($transmission, $exception->getMessage());
        }

        if ($result->status >= 500) {
            return $this->keepWaiting($transmission, $result->errorSummary());
        }

        $errors = $this->resultErrors($result);
        if ($result->successful() && $errors === null) {
            $this->finish($transmission, TransmissionStatusEnum::SUCCEEDED, $result, null);
            app(ApplyRentriTransmissionOutcomeAction::class)->execute($transmission, $result);

            return $transmission;
        }

        return $this->finish($transmission, TransmissionStatusEnum::FAILED, $result, $errors ?? $result->errorSummary());
    }

    private function get(RentriCredential $credential, RentriTransmission $transmission, string $path): RentriResponseData
    {
        return app(PerformRentriRequestAction::class)->execute(
            $credential,
            new RentriRequestData($transmission->serviceEnum(), 'GET', $path),
        );
    }

    private function isDone(RentriResponseData $status): bool
    {
        $value = $status->string(config()->string('rentri.polling.status_key'));
        if ($value === null) {
            return false;
        }
        $done = array_map(
            static fn (mixed $item): string => \is_scalar($item) ? mb_strtolower((string) $item) : '',
            config()->array('rentri.polling.done_values'),
        );

        return \in_array(mb_strtolower($value), $done, true);
    }

    private function resultErrors(RentriResponseData $result): ?string
    {
        foreach (config()->array('rentri.polling.result_error_keys') as $key) {
            if (! \is_string($key)) {
                continue;
            }
            $value = Arr::get($result->body, $key);
            if ($value !== null && $value !== [] && $value !== '') {
                $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                return \is_string($encoded) ? $encoded : $key;
            }
        }

        return null;
    }

    private function keepWaiting(RentriTransmission $transmission, ?string $error): RentriTransmission
    {
        if ($transmission->attempts >= config()->integer('rentri.polling.max_attempts')) {
            return $this->finish($transmission, TransmissionStatusEnum::FAILED, null, RentriException::translate('rentri::exceptions.transmission.polling_timeout', [
                'attempts' => $transmission->attempts,
            ]));
        }

        $transmission->error = $error;
        $transmission->save();

        return $transmission;
    }

    private function finish(RentriTransmission $transmission, TransmissionStatusEnum $status, ?RentriResponseData $response, ?string $error): RentriTransmission
    {
        Assert::true($transmission->status->canTransitionTo($status));

        $transmission->status = $status;
        $transmission->error = $error;
        $transmission->completed_at = CarbonImmutable::now();
        if ($response instanceof RentriResponseData) {
            $transmission->response_status = $response->status;
            $transmission->response_body = $response->storableBody();
        }
        $transmission->save();

        return $transmission;
    }
}
