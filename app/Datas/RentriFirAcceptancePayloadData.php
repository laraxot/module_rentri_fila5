<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Modules\Rentri\Datas\Concerns\MapsToRentriPayload;
use Spatie\LaravelData\Data;

/**
 * Body di POST /formulari/v1.0/{numero_fir}/accettazione.
 *
 * Verificati: `tipoAccettazione` (es. "A"), `quantitaAccettata.valore`, `dataOraArrivo`.
 * DA VERIFICARE: unita' di misura e motivazione del respingimento (percorsi in `rentri.payload.acceptance`).
 */
class RentriFirAcceptancePayloadData extends Data
{
    use MapsToRentriPayload;

    public function __construct(
        public string $acceptanceType,
        public float $acceptedQuantity,
        public string $arrivedAt,
        public ?string $quantityUnit = null,
        public ?string $rejectionReason = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toRentri(): array
    {
        return self::mapWithConfig('acceptance', [
            'quantity_unit' => $this->quantityUnit,
            'rejection_reason' => $this->rejectionReason,
        ], [
            'tipoAccettazione' => $this->acceptanceType,
            'quantitaAccettata' => ['valore' => $this->acceptedQuantity],
            'dataOraArrivo' => $this->arrivedAt,
        ]);
    }
}
