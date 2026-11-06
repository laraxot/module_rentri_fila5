<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Actions\Concerns\ConfiguresRentriJob;
use Modules\Rentri\Actions\Concerns\ResolvesFirNumber;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;

/**
 * Invia a RENTRI l'accettazione del FIR digitale registrata sul Fir (RecordFirAcceptanceAction di Waste):
 * `POST /formulari/v1.0/{numero_fir}/accettazione` con `tipoAccettazione`, `quantitaAccettata.valore`, `dataOraArrivo`.
 *
 * Dopo l'accettazione il formulario passa a FirmaAccettazione: la firma si appone con SignFirAction.
 * Accodabile: `app(SubmitFirAcceptanceAction::class)->onQueue()->execute($fir, $credential)`.
 * Idempotente come IssueDigitalFirAction (exchangeOnce), un tentativo e timeout coerente come job.
 */
class SubmitFirAcceptanceAction
{
    use ConfiguresRentriJob;
    use QueueableAction;
    use ResolvesFirNumber;

    public function execute(Fir $fir, RentriCredential $credential): RentriTransmission
    {
        $payload = app(MapFirAcceptanceToRentriPayloadAction::class)->execute($fir);

        return app(SendRentriRequestAction::class)->exchangeOnce($credential, new RentriRequestData(
            service: RentriServiceEnum::FORMULARI,
            method: 'POST',
            path: $this->firPath($fir, 'accettazione'),
            payload: $payload->toRentri(),
        ), $fir)->transmission;
    }
}
