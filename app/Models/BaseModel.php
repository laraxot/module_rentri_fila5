<?php

declare(strict_types=1);

namespace Modules\Rentri\Models;

use Modules\Xot\Models\XotBaseModel;

/**
 * Modello base del modulo Rentri: connessione `rentri`, chiavi primarie intere.
 *
 * @see XotBaseModel
 */
abstract class BaseModel extends XotBaseModel
{
    /** @var string */
    protected $connection = 'rentri';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            // bigIncrements: XotBaseModel castava l'id a stringa.
            'id' => 'integer',
        ]);
    }
}
