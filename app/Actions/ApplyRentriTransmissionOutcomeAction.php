<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Actions\ChangeFirStatusAction;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\RegisterEntry;
use Spatie\QueueableAction\QueueableAction;

/**
 * Riporta sui modelli Waste l'esito positivo di una trasmissione (subito, se sincrona, o dopo il polling).
 *
 * L'effetto e' scelto da `request_payload.meta.outcome`, scritto da chi ha costruito la richiesta:
 * - fir_issued: numero del FIR digitale restituito da RENTRI su `firs.number`, `is_digital`, stato `issued`;
 * - register_entries: `transmitted_at` e `rentri_transmission_ref` sulle scritture trasmesse.
 *
 * Idempotente: puo' essere richiamata sulla stessa trasmissione senza effetti doppi.
 */
class ApplyRentriTransmissionOutcomeAction
{
    use QueueableAction;

    public const string FIR_ISSUED = 'fir_issued';

    public const string REGISTER_ENTRIES = 'register_entries';

    public function execute(RentriTransmission $transmission, RentriResponseData $response): void
    {
        if ($transmission->status !== TransmissionStatusEnum::SUCCEEDED) {
            return;
        }

        $meta = $transmission->request_payload['meta'] ?? null;
        $meta = \is_array($meta) ? $meta : [];

        match ($meta['outcome'] ?? null) {
            self::FIR_ISSUED => $this->firIssued($transmission, $response),
            self::REGISTER_ENTRIES => $this->registerEntries($transmission, $meta),
            default => null,
        };
    }

    private function firIssued(RentriTransmission $transmission, RentriResponseData $response): void
    {
        $fir = $transmission->subject;
        if (! $fir instanceof Fir) {
            return;
        }

        $number = $response->string(config()->string('rentri.responses.fir_number_key'));
        if ($number !== null && $fir->getAttribute('number') !== $number) {
            $fir->setAttribute('number', $number);
        }
        $fir->setAttribute('is_digital', true);
        $fir->save();

        $issued = FirStatusEnum::from('issued');
        $current = $fir->getAttribute('status');
        if ($current instanceof FirStatusEnum && $current !== $issued && $current->canTransitionTo($issued)) {
            app(ChangeFirStatusAction::class)->execute($fir, $issued);
        }
    }

    /**
     * @param  array<array-key, mixed>  $meta
     */
    private function registerEntries(RentriTransmission $transmission, array $meta): void
    {
        $ids = \is_array($meta['entry_ids'] ?? null) ? array_values(array_filter($meta['entry_ids'], is_int(...))) : [];
        if ($ids === []) {
            return;
        }

        // update di query: non passa dagli eventi del modello, che rendono immutabili le scritture gia' trasmesse.
        RegisterEntry::query()
            ->whereKey($ids)
            ->whereNull('transmitted_at')
            ->update([
                'transmitted_at' => now(),
                'rentri_transmission_ref' => $transmission->transaction_id ?? (string) $transmission->id,
            ]);
    }
}
