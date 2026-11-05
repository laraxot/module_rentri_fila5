<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions\Concerns;

use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriMappingException;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;

/**
 * Numero del FIR digitale da usare nei path `/{numero_fir}/...` del servizio formulari.
 */
trait ResolvesFirNumber
{
    protected function firNumber(Fir $fir): string
    {
        $number = $fir->getAttribute('number');
        if (! \is_string($number) || trim($number) === '') {
            $key = $fir->getKey();
            throw RentriMappingException::missingValue('number', 'Fir#'.(\is_scalar($key) ? (string) $key : '?'));
        }

        return rawurlencode(trim($number));
    }

    protected function firPath(Fir $fir, string $suffix = ''): string
    {
        return '/'.$this->firNumber($fir).($suffix === '' ? '' : '/'.ltrim($suffix, '/'));
    }

    protected function isSuccessful(RentriTransmission $transmission): bool
    {
        return \in_array($transmission->status, [TransmissionStatusEnum::SUBMITTED, TransmissionStatusEnum::SUCCEEDED], true);
    }
}
