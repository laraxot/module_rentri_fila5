<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriCredentialResource\Pages;

use Modules\Rentri\Filament\Resources\RentriCredentialResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseCreateRecord;

class CreateRentriCredential extends XotBaseCreateRecord
{
    protected static string $resource = RentriCredentialResource::class;
}
