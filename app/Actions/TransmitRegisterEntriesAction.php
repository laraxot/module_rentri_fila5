<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Illuminate\Support\Collection;
use Modules\Rentri\Datas\RentriRegisterPayloadData;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriException;
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
 *
 * Niente doppio invio: sotto lock della riga del registro si escludono le scritture gia' contenute in
 * una trasmissione del registro ancora `pending` (in invio) o `submitted` (in attesa di esito). La
 * trasmissione nuova si registra `pending` e si committa prima della chiamata HTTP, come in
 * SendRentriRequestAction::exchangeOnce(): un job ripetuto o due invii in parallelo non mandano due
 * volte la stessa scrittura. Le scritture di una trasmissione `failed` tornano inviabili.
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

        // Registro e trasmissioni possono stare su connessioni diverse: transazione su entrambe, come exchangeOnce().
        $rentri = (new RentriTransmission)->getConnection();
        /** @var array{0: RentriTransmission, 1: RentriRequestData} $claim */
        $claim = $register->getConnection()->transaction(fn (): array => $rentri->transaction(function () use ($register, $credential, $entries, $identifier): array {
            // Serializza gli invii dello stesso registro.
            $register->newQuery()->whereKey($register->getKey())->lockForUpdate()->first();
            $inFlight = $this->inFlightEntryIds($register);

            $selected = ($entries ?? RegisterEntry::query()
                ->where('register_id', $register->getKey())
                ->whereNull('transmitted_at')
                ->get())
                ->filter(static fn (RegisterEntry $entry): bool => $entry->getAttribute('transmitted_at') === null
                    && $entry->getAttribute('register_id') == $register->getKey()
                    && ! \in_array($entry->getKey(), $inFlight, true))
                ->sortBy([['year', 'asc'], ['number', 'asc']])
                ->values();

            if ($selected->isEmpty()) {
                throw new RentriMappingException(RentriException::translate($inFlight === []
                    ? 'rentri::exceptions.register.nothing_to_transmit'
                    : 'rentri::exceptions.register.already_in_transmission'));
            }

            $mapper = app(MapRegisterEntryToRentriPayloadAction::class);
            $payload = new RentriRegisterPayloadData(
                registerIdentifier: $identifier,
                entries: array_values($selected->map(static fn (RegisterEntry $entry) => $mapper->execute($entry))->all()),
            );

            $request = new RentriRequestData(
                service: RentriServiceEnum::DATI_REGISTRI,
                method: 'POST',
                path: str_replace('{identificativo_registro}', rawurlencode($identifier), config()->string('rentri.paths.register_movements')),
                payload: $payload->toRentri(),
                meta: [
                    'outcome' => ApplyRentriTransmissionOutcomeAction::REGISTER_ENTRIES,
                    'entry_ids' => $payload->entryIds(),
                ],
            );

            return [app(SendRentriRequestAction::class)->open($credential, $request, $register, null), $request];
        }));
        [$transmission, $request] = $claim;

        return app(SendRentriRequestAction::class)->send($credential, $request, $transmission)->transmission;
    }

    /**
     * Id delle scritture gia' in una trasmissione del registro non ancora conclusa.
     *
     * @return list<int>
     */
    private function inFlightEntryIds(Register $register): array
    {
        return array_values(RentriTransmission::query()
            ->where('subject_type', $register->getMorphClass())
            ->where('subject_id', $register->getKey())
            ->where('service', RentriServiceEnum::DATI_REGISTRI->value)
            ->whereIn('status', [TransmissionStatusEnum::PENDING->value, TransmissionStatusEnum::SUBMITTED->value])
            ->get()
            ->flatMap(static function (RentriTransmission $transmission): array {
                $ids = data_get($transmission->request_payload, 'meta.entry_ids');

                return \is_array($ids) ? array_values(array_filter($ids, \is_int(...))) : [];
            })
            ->unique()
            ->all());
    }
}
