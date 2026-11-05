<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Spatie\QueueableAction\QueueableAction;

/**
 * Oscura i segreti (PIN, OTP, SAD, password, chiavi...) in un payload prima di salvarlo su `rentri_transmissions`.
 * Le chiavi da oscurare sono in `rentri.redact_keys` e si confrontano senza distinzione di maiuscole, a ogni livello.
 */
class RedactRentriPayloadAction
{
    use QueueableAction;

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, mixed>  $payload
     * @return array<TKey, mixed>
     */
    public function execute(array $payload): array
    {
        $keys = array_values(array_map(
            static fn (mixed $key): string => \is_string($key) ? mb_strtolower($key) : '',
            config()->array('rentri.redact_keys'),
        ));

        return $this->redact($payload, $keys, config()->string('rentri.redacted_value'));
    }

    public function containsRedactedValue(mixed $payload): bool
    {
        $marker = config()->string('rentri.redacted_value');
        if (! \is_array($payload)) {
            return $payload === $marker;
        }
        foreach ($payload as $value) {
            if ($this->containsRedactedValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, mixed>  $payload
     * @param  list<string>  $keys
     * @return array<TKey, mixed>
     */
    private function redact(array $payload, array $keys, string $marker): array
    {
        foreach ($payload as $key => $value) {
            if (\is_string($key) && \in_array(mb_strtolower($key), $keys, true)) {
                $payload[$key] = $marker;

                continue;
            }
            if (\is_array($value)) {
                $payload[$key] = $this->redact($value, $keys, $marker);
            }
        }

        return $payload;
    }
}
