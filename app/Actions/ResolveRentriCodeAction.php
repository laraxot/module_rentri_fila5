<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Exceptions\RentriMappingException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Traduce un valore dei nostri enum (o una stringa) nella codifica RENTRI configurata in `rentri.codes.{list}`.
 * Un valore senza corrispondenza e' un errore di configurazione: meglio fermarsi che inviare un codice inventato.
 */
class ResolveRentriCodeAction
{
    use QueueableAction;

    public function execute(string $list, \BackedEnum|string $value): string
    {
        $key = $value instanceof \BackedEnum ? (string) $value->value : $value;
        $code = config('rentri.codes.'.$list.'.'.$key);

        if (! \is_string($code) || $code === '') {
            throw RentriMappingException::missingCode($list, $key);
        }

        return $code;
    }

    /** Codice EER nel formato RENTRI (senza spazi se `rentri.codes.ewc_strip_spaces`). */
    public function ewc(string $code): string
    {
        $code = trim(str_replace('*', '', $code));

        return config()->boolean('rentri.codes.ewc_strip_spaces') ? str_replace(' ', '', $code) : $code;
    }
}
