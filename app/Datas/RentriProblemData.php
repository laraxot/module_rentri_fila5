<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Spatie\LaravelData\Data;

/**
 * Errore RENTRI in formato application/problem+json (RFC 7807): type, title, status, detail, instance
 * e l'eventuale elenco di errori di validazione.
 */
class RentriProblemData extends Data
{
    /**
     * @param  array<array-key, mixed>  $errors
     */
    public function __construct(
        public ?string $type = null,
        public ?string $title = null,
        public ?int $status = null,
        public ?string $detail = null,
        public ?string $instance = null,
        public array $errors = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $body
     */
    public static function fromBody(array $body): ?self
    {
        $string = static fn (string $key): ?string => isset($body[$key]) && \is_scalar($body[$key]) ? (string) $body[$key] : null;

        $errors = $body['errors'] ?? $body['errori'] ?? [];
        $problem = new self(
            type: $string('type'),
            title: $string('title'),
            status: isset($body['status']) && is_numeric($body['status']) ? (int) $body['status'] : null,
            detail: $string('detail'),
            instance: $string('instance'),
            errors: \is_array($errors) ? $errors : [],
        );

        return $problem->isEmpty() ? null : $problem;
    }

    public function isEmpty(): bool
    {
        return $this->type === null && $this->title === null && $this->detail === null && $this->errors === [];
    }

    /** Testo leggibile per il campo `error` della trasmissione e per i messaggi delle eccezioni. */
    public function summary(): string
    {
        $parts = array_filter([$this->title, $this->detail], static fn (?string $part): bool => $part !== null && $part !== '');
        $summary = implode(' — ', $parts);

        if ($this->errors !== []) {
            $encoded = json_encode($this->errors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $summary .= ($summary === '' ? '' : ' ').(\is_string($encoded) ? $encoded : '');
        }

        return $summary;
    }
}
