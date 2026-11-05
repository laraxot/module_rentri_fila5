<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Modules\Rentri\Datas\RentriExchangeData;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Invia una richiesta a RENTRI e la registra su `rentri_transmissions`.
 *
 * - la richiesta e' salvata oscurata (RedactRentriPayloadAction): PIN, SAD, password non arrivano mai sul database;
 * - risposta con `transazione_id` => `submitted` (esito asincrono, lo legge PollRentriTransactionAction);
 * - 2xx senza transazione => `succeeded`; errore HTTP o di connessione => `failed` con il dettaglio del problem+json.
 *
 * Gli errori HTTP non lanciano eccezioni: l'esito sta nella trasmissione restituita. Lanciano solo i problemi
 * locali (credenziale, certificato), dopo aver segnato la trasmissione come failed.
 */
class SendRentriRequestAction
{
    use QueueableAction;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar>  $query
     */
    public function execute(
        RentriCredential $c,
        string $service,
        string $method,
        string $path,
        array $payload = [],
        array $query = [],
        ?Model $subject = null,
    ): RentriTransmission {
        $request = new RentriRequestData(RentriServiceEnum::from($service), $method, $path, $payload, $query);

        return $this->exchange($c, $request, $subject)->transmission;
    }

    /**
     * Come execute(), ma restituisce anche la risposta completa (body non oscurato, body grezzo):
     * serve alle Actions che ne leggono i dati (hash di firma, SAD, xFIR).
     * Con `$transmission` riusa una trasmissione esistente (reinvio).
     */
    public function exchange(RentriCredential $credential, RentriRequestData $request, ?Model $subject = null, ?RentriTransmission $transmission = null): RentriExchangeData
    {
        $transmission = $this->open($credential, $request, $subject, $transmission);

        try {
            $response = app(PerformRentriRequestAction::class)->execute($credential, $request);
        } catch (ConnectionException $exception) {
            $this->fail($transmission, $exception->getMessage(), config()->integer('rentri.http.retry_times'));

            return new RentriExchangeData($transmission);
        } catch (\Throwable $exception) {
            $this->fail($transmission, $exception->getMessage(), 0);

            throw $exception instanceof RentriException ? $exception : new RentriException($exception->getMessage(), 0, $exception);
        }

        $this->record($transmission, $response);

        if ($transmission->status === TransmissionStatusEnum::SUCCEEDED) {
            app(ApplyRentriTransmissionOutcomeAction::class)->execute($transmission, $response);
        }

        return new RentriExchangeData($transmission, $response);
    }

    private function open(RentriCredential $credential, RentriRequestData $request, ?Model $subject, ?RentriTransmission $transmission): RentriTransmission
    {
        $redactor = app(RedactRentriPayloadAction::class);
        $requestPayload = [
            'body' => $redactor->execute($request->payload),
            'query' => $redactor->execute($request->query),
            'meta' => $request->meta,
        ];

        if ($transmission instanceof RentriTransmission) {
            Assert::true($transmission->status->canTransitionTo(TransmissionStatusEnum::PENDING));
            $transmission->fill([
                'status' => TransmissionStatusEnum::PENDING,
                'error' => null,
                'completed_at' => null,
                'transaction_id' => null,
                'request_payload' => $requestPayload,
            ])->save();

            return $transmission;
        }

        $transmission = new RentriTransmission([
            'rentri_credential_id' => $credential->id,
            'service' => $request->service->value,
            'method' => strtoupper($request->method),
            'path' => '/'.ltrim($request->path, '/'),
            'request_payload' => $requestPayload,
            'status' => TransmissionStatusEnum::PENDING,
            'attempts' => 0,
        ]);
        if ($subject instanceof Model) {
            $transmission->subject()->associate($subject);
        }
        $transmission->save();

        return $transmission;
    }

    private function record(RentriTransmission $transmission, RentriResponseData $response): void
    {
        $transactionId = $response->transactionId();
        $status = match (true) {
            ! $response->successful() => TransmissionStatusEnum::FAILED,
            $transactionId !== null => TransmissionStatusEnum::SUBMITTED,
            default => TransmissionStatusEnum::SUCCEEDED,
        };
        Assert::true($transmission->status->canTransitionTo($status));

        $transmission->fill([
            'response_status' => $response->status,
            'response_body' => $response->storableBody(),
            'transaction_id' => $transactionId,
            'status' => $status,
            'attempts' => $transmission->attempts + $response->attempts,
            'error' => $status === TransmissionStatusEnum::FAILED ? $response->errorSummary() : null,
            'completed_at' => $status->isFinal() ? now() : null,
        ])->save();
    }

    private function fail(RentriTransmission $transmission, string $error, int $attempts): void
    {
        Assert::true($transmission->status->canTransitionTo(TransmissionStatusEnum::FAILED));
        $transmission->fill([
            'status' => TransmissionStatusEnum::FAILED,
            'error' => $error,
            'attempts' => $transmission->attempts + max(1, $attempts),
            'completed_at' => now(),
        ])->save();
    }
}
