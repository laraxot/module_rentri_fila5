<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Actions\Concerns\ResolvesFirNumber;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Trasmissione a RENTRI dei dati del FIR di rifiuti pericolosi da parte dell'operatore (servizio formulari):
 * - FIR digitale (`is_digital` e numero presente): `POST /trasmissioni/operatore/{num_iscr_sito}/{numero_fir}/estrai`,
 *   RENTRI estrae i dati dal formulario gia' presente (nessun body);
 * - FIR cartaceo: `POST /trasmissioni/operatore/{num_iscr_sito}` con i dati del formulario.
 * Endpoint verificati; schema del body per il FIR cartaceo DA VERIFICARE (si riusa MapFirToRentriPayloadAction).
 */
class TransmitHazardousFirDataAction
{
    use QueueableAction;
    use ResolvesFirNumber;

    public function execute(Fir $fir, RentriCredential $credential): RentriTransmission
    {
        $site = $credential->operatorSite;
        Assert::isInstanceOf($site, OperatorSite::class);
        $siteNumber = $site->getAttribute('rentri_site_number');
        Assert::stringNotEmpty($siteNumber);

        $base = '/trasmissioni/operatore/'.rawurlencode($siteNumber);
        $number = $fir->getAttribute('number');

        if ((bool) $fir->getAttribute('is_digital') && \is_string($number) && $number !== '') {
            return app(SendRentriRequestAction::class)->execute(
                $credential,
                RentriServiceEnum::FORMULARI->value,
                'POST',
                $base.'/'.$this->firNumber($fir).'/estrai',
                [],
                [],
                $fir,
            );
        }

        return app(SendRentriRequestAction::class)->execute(
            $credential,
            RentriServiceEnum::FORMULARI->value,
            'POST',
            $base,
            app(MapFirToRentriPayloadAction::class)->execute($fir, $site)->toRentri(),
            [],
            $fir,
        );
    }
}
