<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Modules\Rentri\Datas\Concerns\MapsToRentriPayload;
use Spatie\LaravelData\Data;

/**
 * Una scrittura del registro cronologico di carico/scarico tradotta nelle codifiche RENTRI
 * (MapRegisterEntryToRentriPayloadAction). Struttura JSON in `rentri.payload.register_entry` (DA VERIFICARE).
 */
class RentriRegisterEntryPayloadData extends Data
{
    use MapsToRentriPayload;

    /**
     * @param  list<string>  $hazardProperties
     * @param  list<array{anno: int, progressivo: int}>  $loadReferences
     * @param  array{anno: int, progressivo: int}|null  $rectifies
     */
    public function __construct(
        public int $entryId,
        public int $year,
        public int $number,
        public string $type,
        public string $cause,
        public string $recordedAt,
        public string $operationAt,
        public string $ewcCode,
        public string $physicalState,
        public float $quantity,
        public string $quantityUnit,
        public array $hazardProperties = [],
        public ?string $operation = null,
        public ?string $firNumber = null,
        public ?string $counterpartFiscalCode = null,
        public array $loadReferences = [],
        public ?array $rectifies = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toRentri(): array
    {
        return self::mapWithConfig('register_entry', [
            'year' => $this->year,
            'number' => $this->number,
            'type' => $this->type,
            'cause' => $this->cause,
            'recorded_at' => $this->recordedAt,
            'operation_at' => $this->operationAt,
            'ewc_code' => $this->ewcCode,
            'physical_state' => $this->physicalState,
            'hazard_properties' => $this->hazardProperties,
            'quantity' => $this->quantity,
            'quantity_unit' => $this->quantityUnit,
            'operation' => $this->operation,
            'fir_number' => $this->firNumber,
            'counterpart_fiscal_code' => $this->counterpartFiscalCode,
            'load_references' => $this->loadReferences,
            'rectifies' => $this->rectifies,
            'notes' => $this->notes,
        ]);
    }
}
