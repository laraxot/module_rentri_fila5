<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Modules\Rentri\Filament\Resources\RentriTransmissionResource\Pages\ListRentriTransmissions;
use Modules\Rentri\Filament\Resources\RentriTransmissionResource\Pages\ViewRentriTransmission;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Xot\Filament\Resources\XotBaseResource;

/**
 * Registro delle trasmissioni RENTRI: sola lettura (le scrive solo SendRentriRequestAction).
 */
class RentriTransmissionResource extends XotBaseResource
{
    protected static ?string $model = RentriTransmission::class;

    /**
     * @return array<string, class-string<RelationManager>>
     */
    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListRentriTransmissions::route('/'),
            'view' => ViewRentriTransmission::route('/{record}'),
        ];
    }

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    #[\Override]
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    #[\Override]
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
