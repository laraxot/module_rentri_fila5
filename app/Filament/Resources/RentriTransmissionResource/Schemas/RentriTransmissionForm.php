<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriTransmissionResource\Schemas;

use Filament\Schemas\Components\Component;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Le trasmissioni non si creano ne' si modificano dal pannello: form vuoto, richiesto dalla struttura Xot.
 */
class RentriTransmissionForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [];
    }
}
