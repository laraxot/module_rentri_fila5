<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriTransmissionResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Modules\Rentri\Filament\Actions\PollRentriTransmissionTableAction;
use Modules\Rentri\Filament\Actions\RetryRentriTransmissionTableAction;
use Modules\Rentri\Filament\Resources\RentriTransmissionResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewRentriTransmission extends XotBaseViewRecord
{
    protected static string $resource = RentriTransmissionResource::class;

    /**
     * @return array<string, Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            'poll' => PollRentriTransmissionTableAction::make(),
            'retry' => RetryRentriTransmissionTableAction::make(),
        ];
    }
}
