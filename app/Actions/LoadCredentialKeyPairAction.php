<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Modules\Rentri\Datas\RentriKeyPairData;
use Modules\Rentri\Exceptions\RentriCredentialException;
use Modules\Rentri\Models\RentriCredential;
use Spatie\QueueableAction\QueueableAction;

/**
 * Estrae chiave privata e certificato dal .p12 di interoperabilita' della credenziale (openssl_pkcs12_read).
 *
 * L'header x5c contiene il certificato in DER base64 seguito dall'eventuale catena (`extracerts`).
 * L'algoritmo del JWT segue il tipo di chiave: RS256 per RSA, ES256 per EC.
 */
class LoadCredentialKeyPairAction
{
    use QueueableAction;

    public function execute(RentriCredential $credential): RentriKeyPairData
    {
        $disk = Storage::disk(config()->string('rentri.certificates.disk'));
        if ($credential->certificate_path === '' || ! $disk->exists($credential->certificate_path)) {
            throw RentriCredentialException::certificateMissing($credential);
        }

        $p12 = $disk->get($credential->certificate_path);
        if (! \is_string($p12) || $p12 === '') {
            throw RentriCredentialException::certificateMissing($credential);
        }

        $bundle = [];
        if (! openssl_pkcs12_read($p12, $bundle, $credential->certificate_password) || ! \is_array($bundle)) {
            throw RentriCredentialException::certificateUnreadable($credential, $this->opensslErrors());
        }

        $privateKey = $bundle['pkey'] ?? null;
        $certificate = $bundle['cert'] ?? null;
        if (! \is_string($privateKey) || ! \is_string($certificate)) {
            throw RentriCredentialException::certificateUnreadable($credential, 'pkey/cert');
        }

        $chain = [];
        $extra = $bundle['extracerts'] ?? [];
        if (\is_array($extra)) {
            foreach ($extra as $pem) {
                if (\is_string($pem) && $pem !== '') {
                    $chain[] = $this->pemToDerBase64($pem);
                }
            }
        }

        $parsed = openssl_x509_parse($certificate);
        $validFrom = \is_array($parsed) && isset($parsed['validFrom_time_t']) && \is_int($parsed['validFrom_time_t'])
            ? CarbonImmutable::createFromTimestamp($parsed['validFrom_time_t'])
            : null;
        $validTo = \is_array($parsed) && isset($parsed['validTo_time_t']) && \is_int($parsed['validTo_time_t'])
            ? CarbonImmutable::createFromTimestamp($parsed['validTo_time_t'])
            : null;
        $subject = \is_array($parsed) && isset($parsed['name']) && \is_string($parsed['name']) ? $parsed['name'] : null;

        return new RentriKeyPairData(
            privateKeyPem: $privateKey,
            certificatePem: $certificate,
            x5c: [$this->pemToDerBase64($certificate), ...$chain],
            validFrom: $validFrom,
            validTo: $validTo,
            subject: $subject,
            algorithm: $this->algorithm($privateKey),
        );
    }

    private function pemToDerBase64(string $pem): string
    {
        $base64 = preg_replace('/-----(BEGIN|END)[^-]+-----|\s+/', '', $pem);

        return \is_string($base64) ? $base64 : '';
    }

    private function algorithm(string $privateKeyPem): string
    {
        $key = openssl_pkey_get_private($privateKeyPem);
        if ($key === false) {
            return 'RS256';
        }
        $details = openssl_pkey_get_details($key);

        return \is_array($details) && ($details['type'] ?? null) === OPENSSL_KEYTYPE_EC ? 'ES256' : 'RS256';
    }

    private function opensslErrors(): string
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return $errors === [] ? 'openssl' : implode('; ', $errors);
    }
}
