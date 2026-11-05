<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Modules\Rentri\Datas\RentriKeyPairData;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Models\RentriCredential;
use Spatie\QueueableAction\QueueableAction;

/**
 * Esegue una chiamata firmata verso RENTRI, senza scrivere su database.
 *
 * Header (pattern AgID ModI):
 * - `Authorization: Bearer <JWT>` (ID_AUTH_REST_02, x5c con il certificato di interoperabilita') su ogni richiesta;
 * - se c'e' un body: `Content-Type`, `Digest: SHA-256=<base64>` e `Agid-JWT-Signature: <JWT>` con `signed_headers` (INTEGRITY_REST_02).
 *
 * Il body JSON e' serializzato una sola volta: il digest e' calcolato sugli stessi byte inviati.
 * Retry sugli errori transitori (connessione, `rentri.http.retry_on_status`) con JWT rigenerati a ogni tentativo
 * (jti nuovo). I redirect non vengono seguiti: il 303 del pattern asincrono e' un esito da leggere.
 *
 * @throws ConnectionException se la connessione fallisce anche all'ultimo tentativo
 */
class PerformRentriRequestAction
{
    use QueueableAction;

    public const string CONTENT_TYPE = 'application/json';

    public const string DEFAULT_ACCEPT = 'application/json, application/problem+json';

    public function execute(RentriCredential $credential, RentriRequestData $request, ?RentriKeyPairData $keyPair = null): RentriResponseData
    {
        $keyPair ??= app(LoadCredentialKeyPairAction::class)->execute($credential);
        $url = $this->url($credential, $request);
        $method = strtoupper($request->method);
        $body = $request->payload === [] ? null : $this->encode($request->payload);

        $times = max(1, config()->integer('rentri.http.retry_times'));
        $retryOn = array_map(intval(...), array_filter(config()->array('rentri.http.retry_on_status'), is_numeric(...)));

        for ($attempt = 1; ; $attempt++) {
            $isLast = $attempt >= $times;

            try {
                $response = $this->pending($credential, $keyPair, $request, $body)->send($method, $url);
            } catch (ConnectionException $exception) {
                if ($isLast) {
                    throw $exception;
                }
                $this->pause($attempt);

                continue;
            }

            if (! $isLast && \in_array($response->status(), $retryOn, true)) {
                $this->pause($attempt);

                continue;
            }

            return RentriResponseData::fromResponse($response, $attempt);
        }
    }

    public function url(RentriCredential $credential, RentriRequestData $request): string
    {
        $path = '/'.ltrim($request->path, '/');

        return $credential->environment->baseUrl().'/'.$request->service->basePath().($path === '/' ? '/' : $path);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function pending(RentriCredential $credential, RentriKeyPairData $keyPair, RentriRequestData $request, ?string $body): \Illuminate\Http\Client\PendingRequest
    {
        $headers = [
            'Authorization' => 'Bearer '.app(BuildAuthorizationJwtAction::class)->execute($credential, $keyPair),
            'Accept' => $request->accept ?? self::DEFAULT_ACCEPT,
        ];

        if ($body !== null) {
            $digest = app(BuildDigestHeaderAction::class)->execute($body);
            $headers['Digest'] = $digest;
            $headers['Agid-JWT-Signature'] = app(BuildIntegrityJwtAction::class)->execute($credential, $keyPair, $digest, self::CONTENT_TYPE);
        }

        $pending = Http::withHeaders($headers)
            ->timeout(config()->integer('rentri.http.timeout'))
            ->connectTimeout(config()->integer('rentri.http.connect_timeout'))
            ->withoutRedirecting();

        if ($request->query !== []) {
            $pending = $pending->withQueryParameters($request->query);
        }

        if ($body !== null) {
            $pending = $pending->withBody($body, self::CONTENT_TYPE);
        }

        return $pending;
    }

    private function pause(int $attempt): void
    {
        Sleep::for(config()->integer('rentri.http.retry_sleep_ms') * $attempt)->milliseconds();
    }
}
