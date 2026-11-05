<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Modules\Rentri\Filament\Resources\RentriCredentialResource\Pages\CreateRentriCredential;
use Modules\Rentri\Filament\Resources\RentriCredentialResource\Pages\EditRentriCredential;
use Modules\Rentri\Filament\Resources\RentriCredentialResource\Pages\ListRentriCredentials;
use Modules\Rentri\Models\RentriCredential;
use Modules\Xot\Filament\Resources\XotBaseResource;

class RentriCredentialResource extends XotBaseResource
{
    protected static ?string $model = RentriCredential::class;

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
            'index' => ListRentriCredentials::route('/'),
            'create' => CreateRentriCredential::route('/create'),
            'edit' => EditRentriCredential::route('/{record}/edit'),
        ];
    }
}
