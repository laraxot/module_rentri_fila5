<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

/**
 * Chiave privata e certificato estratti dal .p12 di interoperabilita' (LoadCredentialKeyPairAction).
 *
 * La chiave privata e' `Hidden`: non finisce mai in toArray()/toJson(), quindi non puo' essere loggata per sbaglio.
 */
class RentriKeyPairData extends Data
{
    /**
     * @param  list<string>  $x5c  certificato (DER in base64, non url-safe) seguito dall'eventuale catena
     */
    public function __construct(
        #[Hidden]
        public string $privateKeyPem,
        public string $certificatePem,
        public array $x5c,
        public ?CarbonImmutable $validFrom = null,
        public ?CarbonImmutable $validTo = null,
        public ?string $subject = null,
        public string $algorithm = 'RS256',
    ) {}

    /** Certificato in base64 (DER), come richiesto dal campo `certificato` e dall'header x5c. */
    public function certificateBase64(): string
    {
        return $this->x5c[0];
    }

    /**
     * @return array<string, string|null>
     */
    public function __debugInfo(): array
    {
        return ['certificate_subject' => $this->subject, 'valid_to' => $this->validTo?->toIso8601String()];
    }
}
