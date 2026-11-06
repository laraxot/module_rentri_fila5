<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Spatie\QueueableAction\QueueableAction;

/**
 * Oscura i segreti (PIN, OTP, SAD, password, chiavi...) in un payload prima di salvarlo su `rentri_transmissions`.
 *
 * Le voci di `rentri.redact_keys` sono PAROLE (o sequenze di parole): la chiave si spezza in parole (camelCase,
 * `_`, `-`, spazi) e si oscura se contiene la parola, senza distinzione di maiuscole: `otpCode`, `pinFirma`,
 * `credential_password`, `X-Secret-Key`, `privateKey` sono oscurate; `respingimento` no (contiene "pin" ma non
 * come parola). A ogni livello.
 * Meglio oscurare un campo in piu' che salvare in chiaro un segreto di una chiave nuova (audit sicurezza B7).
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
     * @param  list<string>  $words  voci di `rentri.redact_keys`
     */
    public static function isSecretKey(string $key, array $words): bool
    {
        $haystack = '_'.self::words($key).'_';
        foreach ($words as $word) {
            $needle = self::words($word);
            if ($needle !== '' && str_contains($haystack, '_'.$needle.'_')) {
                return true;
            }
        }

        return false;
    }

    /** `otpCode` / `OTP-code` / `otp_code` → `otp_code`. */
    private static function words(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($value)) ?? $value;

        return trim($value, '_');
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
            if (\is_string($key) && self::isSecretKey($key, $keys)) {
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
