<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Actions\Concerns\ReadsModelAttributes;
use Modules\Rentri\Datas\RentriRegisterEntryPayloadData;
use Modules\Rentri\Exceptions\RentriMappingException;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\RegisterEntry;
use Spatie\QueueableAction\QueueableAction;

/**
 * Traduce una scrittura del registro cronologico (modulo Waste) nel movimento RENTRI: tipo e causale da
 * `rentri.codes.register_entry_type` / `register_cause`, quantita' in kg, riferimenti al FIR, alla controparte,
 * ai carichi d'origine (per gli scarichi) e alla scrittura rettificata.
 */
class MapRegisterEntryToRentriPayloadAction
{
    use QueueableAction;
    use ReadsModelAttributes;

    public function execute(RegisterEntry $entry): RentriRegisterEntryPayloadData
    {
        $codes = app(ResolveRentriCodeAction::class);
        $entryId = $this->attrInt($entry, 'id');
        $subject = 'RegisterEntry#'.$entryId;

        $ewc = EwcCode::query()->find($this->attrInt($entry, 'ewc_code_id'));
        if (! $ewc instanceof EwcCode) {
            throw RentriMappingException::missingValue('ewc_code_id', $subject);
        }

        $recordedAt = $this->attrNullableDate($entry, 'recorded_at');
        $operationAt = $this->attrNullableDate($entry, 'operation_at');
        if ($recordedAt === null || $operationAt === null) {
            throw RentriMappingException::missingValue($recordedAt === null ? 'recorded_at' : 'operation_at', $subject);
        }

        $firId = $this->attrNullableInt($entry, 'fir_id');
        $fir = $firId === null ? null : Fir::query()->find($firId);
        $counterpartId = $this->attrNullableInt($entry, 'counterpart_operator_id');
        $counterpart = $counterpartId === null ? null : Operator::query()->find($counterpartId);
        $operation = $this->attrNullableString($entry, 'operation');
        $rectifiesId = $this->attrNullableInt($entry, 'rectifies_entry_id');
        $rectifies = $rectifiesId === null ? [] : $this->references([$rectifiesId]);

        return new RentriRegisterEntryPayloadData(
            entryId: $entryId,
            year: $this->attrInt($entry, 'year'),
            number: $this->attrInt($entry, 'number'),
            type: $codes->execute('register_entry_type', $this->attrString($entry, 'type')),
            cause: $codes->execute('register_cause', $this->attrString($entry, 'cause')),
            recordedAt: $this->formatRentriDate($recordedAt),
            operationAt: $this->formatRentriDate($operationAt),
            ewcCode: $codes->ewc($this->attrString($ewc, 'code')),
            physicalState: $codes->execute('physical_state', $this->attrString($entry, 'physical_state')),
            quantity: $this->attrFloat($entry, 'quantity_kg'),
            quantityUnit: $codes->execute('quantity_unit', 'kg'),
            hazardProperties: $this->attrStringList($entry, 'hazard_properties'),
            operation: $operation,
            firNumber: $fir instanceof Fir ? $this->attrNullableString($fir, 'number') : null,
            counterpartFiscalCode: $counterpart instanceof Operator ? $this->attrNullableString($counterpart, 'fiscal_code') : null,
            loadReferences: $this->references($this->attrIntList($entry, 'load_entry_ids')),
            rectifies: $rectifies[0] ?? null,
            notes: $this->attrNullableString($entry, 'notes'),
        );
    }

    /**
     * Anno e progressivo delle scritture indicate (riferimenti tra movimenti dello stesso registro).
     *
     * @param  list<int>  $ids
     * @return list<array{anno: int, progressivo: int}>
     */
    private function references(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $references = [];
        /** @var iterable<RegisterEntry> $loaded */
        $loaded = RegisterEntry::query()->whereKey($ids)->orderBy('year')->orderBy('number')->get();
        foreach ($loaded as $entry) {
            $references[] = ['anno' => $this->attrInt($entry, 'year'), 'progressivo' => $this->attrInt($entry, 'number')];
        }

        return $references;
    }
}
