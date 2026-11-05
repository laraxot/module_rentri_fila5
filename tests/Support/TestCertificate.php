<?php

declare(strict_types=1);

namespace Modules\Rentri\Tests\Support;

use Illuminate\Support\Facades\Storage;
use Modules\Rentri\Models\RentriCredential;
use Webmozart\Assert\Assert;

/**
 * Genera al volo con openssl un certificato autofirmato RSA 2048 e il relativo .p12 protetto da password,
 * lo salva sul disco dei certificati (fake nei test) e crea una RentriCredential che lo usa.
 */
final class TestCertificate
{
    public const string PASSWORD = 'test-p12-secret';

    public function __construct(
        public readonly string $p12,
        public readonly string $certificatePem,
        public readonly string $publicKeyPem,
    ) {}

    public static function generate(): self
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (! $key instanceof \OpenSSLAsymmetricKey) {
            throw new \RuntimeException('openssl_pkey_new');
        }
        // openssl_csr_new riceve la chiave per riferimento: si passa una copia per non perdere $key.
        $csrKey = $key;
        $csr = openssl_csr_new(['commonName' => 'Roosko RENTRI Test', 'countryName' => 'IT'], $csrKey, ['digest_alg' => 'sha256']);
        if (! $csr instanceof \OpenSSLCertificateSigningRequest) {
            throw new \RuntimeException('openssl_csr_new');
        }
        $x509 = openssl_csr_sign($csr, null, $key, 30, ['digest_alg' => 'sha256']);
        if (! $x509 instanceof \OpenSSLCertificate) {
            throw new \RuntimeException('openssl_csr_sign');
        }

        Assert::true(openssl_x509_export($x509, $certificatePem));
        Assert::true(openssl_pkcs12_export($x509, $p12, $key, self::PASSWORD));
        $details = openssl_pkey_get_details($key);
        Assert::isArray($details);
        Assert::string($details['key']);
        Assert::string($certificatePem);
        Assert::string($p12);

        return new self($p12, $certificatePem, $details['key']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function credential(array $attributes = []): RentriCredential
    {
        $disk = config()->string('rentri.certificates.disk');
        Storage::fake($disk);
        // Se il disco xFIR e' lo stesso, il fake appena creato vale anche per lui.
        $path = config()->string('rentri.certificates.directory').'/test.p12';
        Storage::disk($disk)->put($path, $this->p12);

        return RentriCredential::factory()->createOne([
            'certificate_path' => $path,
            'certificate_password' => self::PASSWORD,
            'subject_identifier' => '01234567890',
            ...$attributes,
        ]);
    }

    /** Certificato in DER base64, come atteso nell'header x5c. */
    public function x5c(): string
    {
        $base64 = preg_replace('/-----(BEGIN|END)[^-]+-----|\s+/', '', $this->certificatePem);
        Assert::string($base64);

        return $base64;
    }
}
