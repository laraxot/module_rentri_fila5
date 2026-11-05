<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriCredentialResource\Pages;

use Modules\Rentri\Filament\Resources\RentriCredentialResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseEditRecord;

class EditRentriCredential extends XotBaseEditRecord
{
    protected static string $resource = RentriCredentialResource::class;
}
