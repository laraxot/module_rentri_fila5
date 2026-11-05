<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Spatie\LaravelData\Data;

/**
 * Firma (base64) di un digest ottenuta dal servizio di firma remota RENTRI e certificato del firmatario (base64 DER).
 */
class RemoteSignatureData extends Data
{
    public function __construct(
        public string $signature,
        public string $certificate,
    ) {}
}
