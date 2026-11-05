<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Actions;

use Filament\Notifications\Notification;
use Modules\Rentri\Actions\RetryRentriTransmissionAction;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * "Riprova": reinvia una trasmissione fallita (RetryRentriTransmissionAction).
 * Nascosta se il payload salvato contiene segreti oscurati (es. firma con PIN): va ripetuta l'operazione di dominio.
 * Etichette in lang/{locale}/rentri_transmission.php sotto `actions.retry`.
 */
class RetryRentriTransmissionTableAction extends XotBaseAction
{
    public static function getDefaultName(): ?string
    {
        return 'retry';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-arrow-uturn-right')
            ->color('warning')
            ->requiresConfirmation()
            ->authorize('retry')
            ->visible(static fn (RentriTransmission $record): bool => app(RetryRentriTransmissionAction::class)->canRetry($record))
            ->action(static function (RentriTransmission $record): void {
                $result = app(RetryRentriTransmissionAction::class)->execute($record);

                Notification::make()
                    ->title($result->status->getLabel())
                    ->body($result->error)
                    ->color($result->status->getColor())
                    ->send();
            });
    }
}
