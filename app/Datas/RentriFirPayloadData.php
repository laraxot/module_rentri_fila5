<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Modules\Rentri\Datas\Concerns\MapsToRentriPayload;
use Spatie\LaravelData\Data;

/**
 * Dati del formulario da inviare a RENTRI, gia' tradotti nelle codifiche RENTRI (MapFirToRentriPayloadAction).
 * La struttura JSON di uscita e' in config `rentri.payload.fir` (DA VERIFICARE, tranne `num_iscr_sito`).
 */
class RentriFirPayloadData extends Data
{
    use MapsToRentriPayload;

    /**
     * @param  list<string>  $hazardProperties
     */
    public function __construct(
        public string $siteNumber,
        public string $producerFiscalCode,
        public string $producerBusinessName,
        public string $recipientFiscalCode,
        public string $recipientBusinessName,
        public string $ewcCode,
        public string $physicalState,
        public string $operation,
        public float $quantity,
        public string $quantityUnit,
        public bool $isQuantityEstimated = false,
        public array $hazardProperties = [],
        public ?string $producerSiteNumber = null,
        public ?string $recipientSiteNumber = null,
        public ?string $transporterFiscalCode = null,
        public ?string $transporterBusinessName = null,
        public ?string $transporterAlboNumber = null,
        public ?string $intermediaryFiscalCode = null,
        public ?string $wasteDescription = null,
        public ?string $adrUnNumber = null,
        public ?string $vehiclePlate = null,
        public ?string $trailerPlate = null,
        public ?string $driverName = null,
        public ?string $transportStartedAt = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toRentri(): array
    {
        return self::mapWithConfig('fir', [
            'site_number' => $this->siteNumber,
            'producer_fiscal_code' => $this->producerFiscalCode,
            'producer_business_name' => $this->producerBusinessName,
            'producer_site_number' => $this->producerSiteNumber,
            'transporter_fiscal_code' => $this->transporterFiscalCode,
            'transporter_business_name' => $this->transporterBusinessName,
            'transporter_albo_number' => $this->transporterAlboNumber,
            'recipient_fiscal_code' => $this->recipientFiscalCode,
            'recipient_business_name' => $this->recipientBusinessName,
            'recipient_site_number' => $this->recipientSiteNumber,
            'intermediary_fiscal_code' => $this->intermediaryFiscalCode,
            'operation' => $this->operation,
            'ewc_code' => $this->ewcCode,
            'waste_description' => $this->wasteDescription,
            'physical_state' => $this->physicalState,
            'hazard_properties' => $this->hazardProperties,
            'quantity' => $this->quantity,
            'quantity_unit' => $this->quantityUnit,
            'is_quantity_estimated' => $this->isQuantityEstimated,
            'adr_un_number' => $this->adrUnNumber,
            'vehicle_plate' => $this->vehiclePlate,
            'trailer_plate' => $this->trailerPlate,
            'driver_name' => $this->driverName,
            'transport_started_at' => $this->transportStartedAt,
            'notes' => $this->notes,
        ]);
    }
}
