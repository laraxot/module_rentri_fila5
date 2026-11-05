<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Exceptions\RentriException;
use Illuminate\Support\Collection;
use Modules\Rentri\Datas\RentriRegisterPayloadData;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Exceptions\RentriMappingException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Register;
use Modules\Waste\Models\RegisterEntry;
use Spatie\QueueableAction\QueueableAction;

/**
 * Trasmette a RENTRI le scritture del registro cronologico di carico/scarico.
 *
 * Default: tutte le scritture del registro non ancora trasmesse (`transmitted_at` null), in ordine di anno e numero.
 * Le scritture gia' trasmesse passate esplicitamente vengono ignorate.
 * DA VERIFICARE: servizio `dati_registri`, path `rentri.paths.register_movements`, schema del movimento.
 * A esito positivo (subito o dopo il polling) ApplyRentriTransmissionOutcomeAction valorizza `transmitted_at`
 * e `rentri_transmission_ref`; gli id inviati stanno in `request_payload.meta.entry_ids`.
 */
class TransmitRegisterEntriesAction
{
    use QueueableAction;

    /**
     * @param  Collection<int, RegisterEntry>|null  $entries
     */
    public function execute(Register $register, RentriCredential $credential, ?Collection $entries = null): RentriTransmission
    {
        $identifier = $register->getAttribute('rentri_identifier');
        if (! \is_string($identifier) || $identifier === '') {
            $key = $register->getKey();
            throw RentriMappingException::missingValue('rentri_identifier', 'Register#'.(\is_scalar($key) ? (string) $key : '?'));
        }

        $entries = ($entries ?? RegisterEntry::query()
            ->where('register_id', $register->getKey())
            ->whereNull('transmitted_at')
            ->get())
            ->filter(static fn (RegisterEntry $entry): bool => $entry->getAttribute('transmitted_at') === null
                && $entry->getAttribute('register_id') == $register->getKey())
            ->sortBy([['year', 'asc'], ['number', 'asc']])
            ->values();

        if ($entries->isEmpty()) {
            throw new RentriMappingException(RentriException::translate('rentri::exceptions.register.nothing_to_transmit'));
        }

        $mapper = app(MapRegisterEntryToRentriPayloadAction::class);
        $payload = new RentriRegisterPayloadData(
            registerIdentifier: $identifier,
            entries: array_values($entries->map(static fn (RegisterEntry $entry) => $mapper->execute($entry))->all()),
        );

        return app(SendRentriRequestAction::class)->exchange($credential, new RentriRequestData(
            service: RentriServiceEnum::DATI_REGISTRI,
            method: 'POST',
            path: str_replace('{identificativo_registro}', rawurlencode($identifier), config()->string('rentri.paths.register_movements')),
            payload: $payload->toRentri(),
            meta: [
                'outcome' => ApplyRentriTransmissionOutcomeAction::REGISTER_ENTRIES,
                'entry_ids' => $payload->entryIds(),
            ],
        ), $register)->transmission;
    }
}
