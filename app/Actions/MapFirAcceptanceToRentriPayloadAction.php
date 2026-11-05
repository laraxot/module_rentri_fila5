<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Actions\Concerns\ReadsModelAttributes;
use Modules\Rentri\Datas\RentriFirAcceptancePayloadData;
use Modules\Rentri\Exceptions\RentriMappingException;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;

/**
 * Dati di accettazione del Fir (registrati da RecordFirAcceptanceAction del modulo Waste) nel body di
 * POST /{numero_fir}/accettazione: `tipoAccettazione` da `rentri.codes.acceptance`, `quantitaAccettata.valore`
 * in kg da `accepted_quantity_kg`, `dataOraArrivo` da `arrived_at`.
 */
class MapFirAcceptanceToRentriPayloadAction
{
    use QueueableAction;
    use ReadsModelAttributes;

    public function execute(Fir $fir): RentriFirAcceptancePayloadData
    {
        $subject = 'Fir#'.$this->attrInt($fir, 'id');
        $arrivedAt = $this->attrNullableDate($fir, 'arrived_at');
        if ($arrivedAt === null) {
            throw RentriMappingException::missingValue('arrived_at', $subject);
        }

        $codes = app(ResolveRentriCodeAction::class);

        return new RentriFirAcceptancePayloadData(
            acceptanceType: $codes->execute('acceptance', $this->attrString($fir, 'acceptance')),
            acceptedQuantity: $this->attrNullableFloat($fir, 'accepted_quantity_kg') ?? 0.0,
            arrivedAt: $this->formatRentriDate($arrivedAt),
            quantityUnit: $codes->execute('quantity_unit', 'kg'),
            rejectionReason: $this->attrNullableString($fir, 'rejection_reason'),
        );
    }
}
