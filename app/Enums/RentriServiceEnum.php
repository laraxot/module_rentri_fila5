<?php

declare(strict_types=1);

namespace Modules\Rentri\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Servizi di interoperabilita' RENTRI usati dal modulo.
 *
 * Il base path di ogni servizio sta in config `rentri.services.{value}`: verificato solo per `formulari`
 * (`formulari/v1.0`), gli altri sono DA VERIFICARE sullo Swagger demo.
 *
 * Etichette: Modules/Rentri/lang/{locale}/rentri_service_enum.php.
 */
enum RentriServiceEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case FORMULARI = 'formulari';
    case ANAGRAFICHE = 'anagrafiche';
    case CODIFICHE = 'codifiche';
    case DATI_REGISTRI = 'dati_registri';
    case VIDIMAZIONE_FORMULARI = 'vidimazione_formulari';
    case SIGN = 'sign';

    public function basePath(): string
    {
        return trim(config()->string('rentri.services.'.$this->value), '/');
    }

    /**
     * Servizi che espongono un endpoint di stato interrogabile dalla pagina RentriStatusPage.
     *
     * @return list<self>
     */
    public static function monitored(): array
    {
        return [self::FORMULARI, self::ANAGRAFICHE, self::CODIFICHE, self::DATI_REGISTRI, self::VIDIMAZIONE_FORMULARI];
    }
}
