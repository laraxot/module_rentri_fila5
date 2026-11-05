<?php

declare(strict_types=1);

namespace Modules\Rentri\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Stato di una chiamata verso RENTRI registrata in `rentri_transmissions`.
 *
 * - pending: registrata, richiesta non ancora inviata (o reinvio in corso);
 * - submitted: accettata da RENTRI con `transazione_id`, esito asincrono da verificare con il polling;
 * - succeeded / failed: esito definitivo.
 *
 * Etichette: Modules/Rentri/lang/{locale}/transmission_status_enum.php.
 */
enum TransmissionStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::PENDING => \in_array($to, [self::SUBMITTED, self::SUCCEEDED, self::FAILED], true),
            self::SUBMITTED => \in_array($to, [self::SUCCEEDED, self::FAILED], true),
            // "riprova": una trasmissione fallita torna in coda.
            self::FAILED => $to === self::PENDING,
            self::SUCCEEDED => false,
        };
    }

    public function isFinal(): bool
    {
        return $this === self::SUCCEEDED || $this === self::FAILED;
    }
}
