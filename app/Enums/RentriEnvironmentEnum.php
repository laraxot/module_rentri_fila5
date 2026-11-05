<?php

declare(strict_types=1);

namespace Modules\Rentri\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Ambiente RENTRI di una credenziale. URL base (verificati) e audience del JWT (DA VERIFICARE) in config `rentri.*`.
 *
 * Etichette: Modules/Rentri/lang/{locale}/rentri_environment_enum.php.
 */
enum RentriEnvironmentEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case DEMO = 'demo';
    case PRODUCTION = 'production';

    public function baseUrl(): string
    {
        return rtrim(config()->string('rentri.environments.'.$this->value.'.base_url'), '/');
    }

    public function audience(): string
    {
        return config()->string('rentri.jwt.audience.'.$this->value);
    }
}
