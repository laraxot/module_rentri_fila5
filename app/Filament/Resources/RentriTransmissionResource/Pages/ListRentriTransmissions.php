<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriTransmissionResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Modules\Rentri\Filament\Resources\RentriTransmissionResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

class ListRentriTransmissions extends XotBaseListRecords
{
    protected static string $resource = RentriTransmissionResource::class;

    /**
     * Sola lettura: niente "crea".
     *
     * @return array<string, Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
