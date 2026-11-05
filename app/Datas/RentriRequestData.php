<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Models\RentriTransmission;
use Spatie\LaravelData\Data;

/**
 * Richiesta verso RENTRI: servizio, metodo, path relativo al base path del servizio, body JSON e query.
 */
class RentriRequestData extends Data
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar>  $query
     * @param  array<string, mixed>  $meta  dati interni salvati con la trasmissione e MAI inviati (es. id delle scritture di registro)
     */
    public function __construct(
        public RentriServiceEnum $service,
        public string $method,
        public string $path,
        public array $payload = [],
        public array $query = [],
        public array $meta = [],
        public ?string $accept = null,
    ) {}

    /**
     * Ricostruisce la richiesta da una trasmissione registrata (per "riprova").
     * Il payload salvato e' gia' oscurato: chi lo reinvia deve controllarlo (RetryRentriTransmissionAction).
     */
    public static function fromTransmission(RentriTransmission $transmission): self
    {
        $payload = $transmission->request_payload ?? [];
        /** @var array<string, mixed> $body */
        $body = \is_array($payload['body'] ?? null) ? $payload['body'] : [];
        /** @var array<string, scalar> $query */
        $query = \is_array($payload['query'] ?? null) ? $payload['query'] : [];
        /** @var array<string, mixed> $meta */
        $meta = \is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        return new self(
            service: RentriServiceEnum::from($transmission->service),
            method: $transmission->method,
            path: $transmission->path,
            payload: $body,
            query: $query,
            meta: $meta,
        );
    }
}
