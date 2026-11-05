<?php

declare(strict_types=1);

namespace Modules\Rentri\Exceptions;

/**
 * Radice delle eccezioni di dominio del modulo Rentri.
 */
class RentriException extends \RuntimeException
{
    /**
     * Messaggio tradotto da `lang/{locale}/exceptions.php` (o altra chiave `rentri::`).
     *
     * @param  array<string, string|int|float>  $replace
     */
    public static function translate(string $key, array $replace = []): string
    {
        $message = __($key, $replace);

        return \is_string($message) ? $message : $key;
    }
}
