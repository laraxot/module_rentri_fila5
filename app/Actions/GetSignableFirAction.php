<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;

/**
 * FIR digitale da firmare a partire da una trasmissione riuscita: l'emissione (`POST /`, firma del
 * produttore) o l'accettazione (`POST /{numero}/accettazione`, firma dell'accettazione). Null se la
 * trasmissione non e' una di queste, non e' riuscita o il FIR non ha ancora il numero RENTRI.
 */
class GetSignableFirAction
{
    use QueueableAction;

    public function execute(RentriTransmission $transmission): ?Fir
    {
        if ($transmission->status !== TransmissionStatusEnum::SUCCEEDED
            || $transmission->service !== RentriServiceEnum::FORMULARI->value
            || $transmission->method !== 'POST'
            || ! ($transmission->path === '/' || str_ends_with($transmission->path, '/accettazione'))) {
            return null;
        }

        $fir = $transmission->subject;

        return $fir instanceof Fir && $fir->is_digital && $fir->number !== null && $fir->number !== '' ? $fir : null;
    }
}
