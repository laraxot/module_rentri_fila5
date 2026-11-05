<?php

declare(strict_types=1);

namespace Modules\Rentri\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Esito del controllo di stato di un servizio RENTRI (CheckRentriServiceStatusAction).
 *
 * Etichette: Modules/Rentri/lang/{locale}/rentri_service_health_enum.php.
 */
enum RentriServiceHealthEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case OK = 'ok';
    case WARNING = 'warning';
    case ERROR = 'error';
}
