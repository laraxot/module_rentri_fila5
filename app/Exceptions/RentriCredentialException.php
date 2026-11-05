<?php

declare(strict_types=1);

namespace Modules\Rentri\Exceptions;

use Modules\Rentri\Models\RentriCredential;

/**
 * Credenziale RENTRI assente, scaduta o certificato .p12 non leggibile.
 * Il messaggio non contiene mai la password ne' materiale della chiave.
 */
class RentriCredentialException extends RentriException
{
    public static function notFoundForSite(int $siteId): self
    {
        return new self(RentriException::translate('rentri::exceptions.credential.not_found', ['site' => $siteId]));
    }

    public static function certificateMissing(RentriCredential $credential): self
    {
        return new self(RentriException::translate('rentri::exceptions.credential.certificate_missing', ['credential' => $credential->name]));
    }

    public static function certificateUnreadable(RentriCredential $credential, string $opensslError): self
    {
        return new self(RentriException::translate('rentri::exceptions.credential.certificate_unreadable', [
            'credential' => $credential->name,
            'error' => $opensslError,
        ]));
    }

    public static function remoteSignatureNotConfigured(RentriCredential $credential): self
    {
        return new self(RentriException::translate('rentri::exceptions.credential.remote_signature_missing', ['credential' => $credential->name]));
    }
}
