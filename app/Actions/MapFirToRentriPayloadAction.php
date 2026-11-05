<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Actions\Concerns\ReadsModelAttributes;
use Modules\Rentri\Datas\RentriFirPayloadData;
use Modules\Rentri\Exceptions\RentriMappingException;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;

/**
 * Traduce un Fir (modulo Waste) nei dati del formulario RENTRI: anagrafiche dei soggetti, EER, stato fisico,
 * caratteristiche di pericolo, operazione, quantita' e trasporto, con le codifiche di `rentri.codes.*`.
 *
 * `$issuerSite` e' l'unita' locale che emette il formulario: il suo `rentri_site_number` va in `num_iscr_sito`.
 */
class MapFirToRentriPayloadAction
{
    use QueueableAction;
    use ReadsModelAttributes;

    public function execute(Fir $fir, OperatorSite $issuerSite): RentriFirPayloadData
    {
        $codes = app(ResolveRentriCodeAction::class);
        $subject = 'Fir#'.$this->attrInt($fir, 'id');

        $producer = $this->operator($this->attrInt($fir, 'producer_operator_id'), 'producer_operator_id', $subject);
        $recipient = $this->operator($this->attrInt($fir, 'recipient_operator_id'), 'recipient_operator_id', $subject);
        $transporterId = $this->attrNullableInt($fir, 'transporter_operator_id');
        $transporter = $transporterId === null ? null : $this->operator($transporterId, 'transporter_operator_id', $subject);
        $intermediaryId = $this->attrNullableInt($fir, 'intermediary_operator_id');
        $intermediary = $intermediaryId === null ? null : $this->operator($intermediaryId, 'intermediary_operator_id', $subject);

        $ewc = EwcCode::query()->find($this->attrInt($fir, 'ewc_code_id'));
        if (! $ewc instanceof EwcCode) {
            throw RentriMappingException::missingValue('ewc_code_id', $subject);
        }

        $transportStartedAt = $this->attrNullableDate($fir, 'transport_started_at');

        return new RentriFirPayloadData(
            siteNumber: $this->attrString($issuerSite, 'rentri_site_number'),
            producerFiscalCode: $this->attrString($producer, 'fiscal_code'),
            producerBusinessName: $this->attrString($producer, 'business_name'),
            recipientFiscalCode: $this->attrString($recipient, 'fiscal_code'),
            recipientBusinessName: $this->attrString($recipient, 'business_name'),
            ewcCode: $codes->ewc($this->attrString($ewc, 'code')),
            physicalState: $codes->execute('physical_state', $this->attrString($fir, 'physical_state')),
            operation: $this->attrString($fir, 'operation'),
            quantity: $this->attrFloat($fir, 'quantity'),
            quantityUnit: $codes->execute('quantity_unit', $this->attrString($fir, 'quantity_unit')),
            isQuantityEstimated: $this->attrBool($fir, 'is_quantity_estimated'),
            hazardProperties: $this->attrStringList($fir, 'hazard_properties'),
            producerSiteNumber: $this->siteNumber($this->attrNullableInt($fir, 'producer_site_id')),
            recipientSiteNumber: $this->siteNumber($this->attrNullableInt($fir, 'recipient_site_id')),
            transporterFiscalCode: $transporter === null ? null : $this->attrString($transporter, 'fiscal_code'),
            transporterBusinessName: $transporter === null ? null : $this->attrNullableString($transporter, 'business_name'),
            transporterAlboNumber: $transporter === null ? null : $this->attrNullableString($transporter, 'albo_registration_number'),
            intermediaryFiscalCode: $intermediary === null ? null : $this->attrString($intermediary, 'fiscal_code'),
            wasteDescription: $this->attrNullableString($fir, 'waste_description'),
            adrUnNumber: $this->attrNullableString($fir, 'adr_un_number'),
            vehiclePlate: $this->attrNullableString($fir, 'vehicle_plate'),
            trailerPlate: $this->attrNullableString($fir, 'trailer_plate'),
            driverName: $this->attrNullableString($fir, 'driver_name'),
            transportStartedAt: $transportStartedAt === null ? null : $this->formatRentriDate($transportStartedAt),
            notes: $this->attrNullableString($fir, 'notes'),
        );
    }

    private function operator(int $id, string $field, string $subject): Operator
    {
        $operator = Operator::query()->find($id);
        if (! $operator instanceof Operator) {
            throw RentriMappingException::missingValue($field, $subject);
        }

        return $operator;
    }

    private function siteNumber(?int $siteId): ?string
    {
        if ($siteId === null) {
            return null;
        }
        $site = OperatorSite::query()->find($siteId);

        return $site instanceof OperatorSite ? $this->attrNullableString($site, 'rentri_site_number') : null;
    }
}
