<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Crea il FIR digitale su RENTRI: `POST /formulari/v1.0/` (verificato) con i dati di MapFirToRentriPayloadAction.
 *
 * L'unita' locale che emette il formulario e' quella della credenziale. A esito positivo (sincrono o dopo il polling)
 * ApplyRentriTransmissionOutcomeAction salva il numero restituito su `firs.number` e porta il Fir a `issued`.
 * Accodabile: `app(IssueDigitalFirAction::class)->onQueue()->execute($fir, $credential)`.
 */
class IssueDigitalFirAction
{
    use QueueableAction;

    public function execute(Fir $fir, RentriCredential $credential): RentriTransmission
    {
        $site = $credential->operatorSite;
        Assert::isInstanceOf($site, OperatorSite::class);

        $payload = app(MapFirToRentriPayloadAction::class)->execute($fir, $site);

        return app(SendRentriRequestAction::class)->exchange($credential, new RentriRequestData(
            service: RentriServiceEnum::FORMULARI,
            method: 'POST',
            path: '/',
            payload: $payload->toRentri(),
            meta: ['outcome' => ApplyRentriTransmissionOutcomeAction::FIR_ISSUED],
        ), $fir)->transmission;
    }
}
