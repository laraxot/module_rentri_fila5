<?php

declare(strict_types=1);

namespace Modules\Rentri\Exceptions;

/**
 * Un valore dei nostri modelli non ha corrispondenza nelle codifiche RENTRI configurate (`rentri.codes.*`)
 * oppure manca un dato obbligatorio per costruire il payload.
 */
class RentriMappingException extends RentriException
{
    public static function missingCode(string $list, string $value): self
    {
        return new self(RentriException::translate('rentri::exceptions.mapping.missing_code', ['list' => $list, 'value' => $value]));
    }

    public static function missingValue(string $field, string $subject): self
    {
        return new self(RentriException::translate('rentri::exceptions.mapping.missing_value', ['field' => $field, 'subject' => $subject]));
    }
}
