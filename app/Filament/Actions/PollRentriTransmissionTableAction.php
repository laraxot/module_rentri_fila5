<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Actions;

use Filament\Notifications\Notification;
use Modules\Rentri\Actions\PollRentriTransactionAction;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * "Verifica esito": interroga subito RENTRI per una trasmissione `submitted` (PollRentriTransactionAction).
 * Etichette in lang/{locale}/rentri_transmission.php sotto `actions.poll`.
 */
class PollRentriTransmissionTableAction extends XotBaseAction
{
    public static function getDefaultName(): ?string
    {
        return 'poll';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-arrow-path')
            ->color('info')
            ->authorize('retry')
            ->visible(static fn (RentriTransmission $record): bool => $record->status === TransmissionStatusEnum::SUBMITTED)
            ->action(static function (RentriTransmission $record): void {
                $result = app(PollRentriTransactionAction::class)->execute($record);

                Notification::make()
                    ->title($result->status->getLabel())
                    ->body($result->error)
                    ->color($result->status->getColor())
                    ->send();
            });
    }
}
