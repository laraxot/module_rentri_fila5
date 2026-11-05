<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Modules\Rentri\Exceptions\RentriMappingException;

/**
 * Lettura tipizzata degli attributi dei modelli Waste (che il modulo legge ma non possiede):
 * i valori arrivano come `mixed` da getAttribute() e qui vengono ricondotti ai tipi attesi.
 */
trait ReadsModelAttributes
{
    protected function attrString(Model $model, string $key): string
    {
        $value = $this->attrNullableString($model, $key);
        if ($value === null) {
            throw RentriMappingException::missingValue($key, class_basename($model).'#'.$this->keyOf($model));
        }

        return $value;
    }

    protected function attrNullableString(Model $model, string $key): ?string
    {
        $value = $model->getAttribute($key);
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if (\is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        return null;
    }

    protected function attrNullableInt(Model $model, string $key): ?int
    {
        $value = $model->getAttribute($key);

        return is_numeric($value) ? (int) $value : null;
    }

    protected function attrInt(Model $model, string $key): int
    {
        $value = $this->attrNullableInt($model, $key);
        if ($value === null) {
            throw RentriMappingException::missingValue($key, class_basename($model).'#'.$this->keyOf($model));
        }

        return $value;
    }

    protected function attrNullableFloat(Model $model, string $key): ?float
    {
        $value = $model->getAttribute($key);

        return is_numeric($value) ? (float) $value : null;
    }

    protected function attrFloat(Model $model, string $key): float
    {
        $value = $this->attrNullableFloat($model, $key);
        if ($value === null) {
            throw RentriMappingException::missingValue($key, class_basename($model).'#'.$this->keyOf($model));
        }

        return $value;
    }

    protected function attrBool(Model $model, string $key): bool
    {
        return (bool) $model->getAttribute($key);
    }

    protected function attrNullableDate(Model $model, string $key): ?CarbonImmutable
    {
        $value = $model->getAttribute($key);
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }
        if (\is_string($value) && $value !== '') {
            return CarbonImmutable::parse($value);
        }

        return null;
    }

    /**
     * Valori di un attributo lista (json di enum o di stringhe) come stringhe.
     *
     * @return list<string>
     */
    protected function attrStringList(Model $model, string $key): array
    {
        $value = $model->getAttribute($key);
        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            $value = \is_array($decoded) ? $decoded : [];
        }
        if (! is_iterable($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if ($item instanceof \BackedEnum) {
                $out[] = (string) $item->value;
            } elseif (\is_scalar($item) && (string) $item !== '') {
                $out[] = (string) $item;
            }
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    protected function attrIntList(Model $model, string $key): array
    {
        return array_values(array_map(
            intval(...),
            array_filter($this->attrStringList($model, $key), is_numeric(...)),
        ));
    }

    /** Data/ora nel formato RENTRI (`rentri.datetime_format`, fuso `rentri.timezone`). */
    protected function formatRentriDate(\DateTimeInterface $date): string
    {
        return CarbonImmutable::instance($date)
            ->setTimezone(config()->string('rentri.timezone'))
            ->format(config()->string('rentri.datetime_format'));
    }

    private function keyOf(Model $model): string
    {
        $key = $model->getKey();

        return \is_scalar($key) ? (string) $key : '?';
    }
}
