<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriCredentialResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

/**
 * Nessuna voce per password o contenuto del certificato.
 */
class RentriCredentialInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'name' => TextEntry::make('name'),
            'operator_site_id' => TextEntry::make('operatorSite.name'),
            'environment' => TextEntry::make('environment')->badge(),
            'subject_identifier' => TextEntry::make('subject_identifier'),
            'remote_signature_credential_id' => TextEntry::make('remote_signature_credential_id'),
            'expires_at' => TextEntry::make('expires_at')->dateTime(),
            'is_active' => IconEntry::make('is_active')->boolean(),
            'updated_at' => TextEntry::make('updated_at')->dateTime(),
        ];
    }
}
