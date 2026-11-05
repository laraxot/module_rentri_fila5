<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas\Concerns;

use Illuminate\Support\Arr;

/**
 * Trasforma i valori di dominio (nostre chiavi snake_case) nel JSON RENTRI secondo la mappa di percorsi
 * `config('rentri.payload.{key}')` (dot notation). I valori null o vuoti, o senza percorso configurato, non vengono inviati.
 */
trait MapsToRentriPayload
{
    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $base  campi fissi, gia' nel formato RENTRI (quelli verificati)
     * @return array<string, mixed>
     */
    protected static function mapWithConfig(string $configKey, array $values, array $base = []): array
    {
        /** @var array<string, mixed> $paths */
        $paths = config()->array('rentri.payload.'.$configKey);
        $out = $base;

        foreach ($values as $key => $value) {
            $path = $paths[$key] ?? null;
            if (! \is_string($path) || $path === '' || $value === null || $value === '' || $value === []) {
                continue;
            }
            Arr::set($out, $path, $value);
        }

        /** @var array<string, mixed> $out */
        return $out;
    }
}
