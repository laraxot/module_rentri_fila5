<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

/**
 * Risposta di POST /formulari/v1.0/{numero_fir}/hash (campi verificati): token della sessione di firma,
 * digest da firmare (base64) e algoritmo di hash (`SHA256`).
 */
class RentriFirSignatureHashData extends Data
{
    public function __construct(
        public string $token,
        #[MapInputName('digest_to_sign')]
        public string $digestToSign,
        #[MapInputName('hash_algorithm')]
        public string $hashAlgorithm,
    ) {}
}
