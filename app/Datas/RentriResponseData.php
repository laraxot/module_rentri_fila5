<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

/**
 * Risposta HTTP di RENTRI normalizzata: codice, body JSON decodificato (vuoto se non JSON), body grezzo
 * (per gli xFIR), content type e Location (redirect 303 del pattern asincrono).
 */
class RentriResponseData extends Data
{
    /**
     * @param  array<array-key, mixed>  $body
     */
    public function __construct(
        public int $status,
        public array $body = [],
        #[Hidden]
        public string $raw = '',
        public ?string $contentType = null,
        public ?string $location = null,
        public int $attempts = 1,
    ) {}

    public static function fromResponse(Response $response, int $attempts = 1): self
    {
        $raw = $response->body();
        $decoded = $raw === '' ? null : json_decode($raw, true);
        $contentType = $response->header('Content-Type');
        $location = $response->header('Location');

        return new self(
            status: $response->status(),
            body: \is_array($decoded) ? $decoded : [],
            raw: $raw,
            contentType: $contentType === '' ? null : $contentType,
            location: $location === '' ? null : $location,
            attempts: $attempts,
        );
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isSeeOther(): bool
    {
        return $this->status === 303;
    }

    /** Identificativo della transazione asincrona (`transazione_id`), se presente. */
    public function transactionId(): ?string
    {
        return $this->string(config()->string('rentri.responses.transaction_id_key'));
    }

    /** Valore scalare del body in dot notation, come stringa. */
    public function string(string $key): ?string
    {
        $value = Arr::get($this->body, $key);

        return \is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    public function problem(): ?RentriProblemData
    {
        return $this->successful() ? null : RentriProblemData::fromBody($this->body);
    }

    /**
     * Body da salvare su rentri_transmissions: JSON oscurato, oppure solo tipo e dimensione se il body non e' JSON (xFIR).
     *
     * @return array<array-key, mixed>
     */
    public function storableBody(): array
    {
        if ($this->body !== []) {
            return app(\Modules\Rentri\Actions\RedactRentriPayloadAction::class)->execute($this->body);
        }

        if ($this->raw === '') {
            return [];
        }

        return ['content_type' => $this->contentType, 'length' => \strlen($this->raw)];
    }

    public function errorSummary(): string
    {
        $problem = $this->problem();
        if ($problem instanceof RentriProblemData) {
            return $problem->summary();
        }

        return 'HTTP '.$this->status;
    }
}
