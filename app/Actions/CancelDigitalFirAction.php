<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Actions\Concerns\ResolvesFirNumber;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;

/**
 * Annulla il FIR digitale: `POST /{numero_fir}/annulla-fir` e, se ci sono note, `POST /{numero_fir}/note-annullamento`
 * con il campo `note` (endpoint e campo verificati; ordine delle due chiamate DA VERIFICARE).
 *
 * L'annullamento porta il formulario in FirmaAnnullamento: diventa Annullato dopo la firma (SignFirAction).
 * Lo stato locale del Fir (`cancelled`) lo cambia chi chiama, a firma avvenuta, con ChangeFirStatusAction di Waste.
 * Restituisce la trasmissione di annulla-fir.
 */
class CancelDigitalFirAction
{
    use QueueableAction;
    use ResolvesFirNumber;

    public function execute(Fir $fir, RentriCredential $credential, ?string $notes = null): RentriTransmission
    {
        $send = app(SendRentriRequestAction::class);

        $transmission = $send->execute($credential, RentriServiceEnum::FORMULARI->value, 'POST', $this->firPath($fir, 'annulla-fir'), [], [], $fir);

        if ($notes !== null && trim($notes) !== '' && $this->isSuccessful($transmission)) {
            $send->execute(
                $credential,
                RentriServiceEnum::FORMULARI->value,
                'POST',
                $this->firPath($fir, 'note-annullamento'),
                [config()->string('rentri.payload.cancellation_notes') => trim($notes)],
                [],
                $fir,
            );
        }

        return $transmission;
    }
}
