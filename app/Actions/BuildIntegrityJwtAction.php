<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Datas\RentriKeyPairData;
use Modules\Rentri\Models\RentriCredential;
use Spatie\QueueableAction\QueueableAction;

/**
 * JWT del pattern ModI INTEGRITY_REST_02, inviato nell'header `Agid-JWT-Signature`.
 *
 * Stessi claim del token di autorizzazione piu' `signed_headers` con `digest` e `content-type`, cosi' RENTRI
 * puo' verificare che il body ricevuto sia quello firmato. Forma di `signed_headers` (lista di oggetti come nelle
 * Linee guida ModI, oppure oggetto unico) in `rentri.jwt.signed_headers_as_list` — DA VERIFICARE.
 */
class BuildIntegrityJwtAction
{
    use QueueableAction;

    public function execute(RentriCredential $credential, RentriKeyPairData $keyPair, string $digest, string $contentType): string
    {
        $signedHeaders = config()->boolean('rentri.jwt.signed_headers_as_list')
            ? [['digest' => $digest], ['content-type' => $contentType]]
            : ['digest' => $digest, 'content-type' => $contentType];

        return app(BuildAuthorizationJwtAction::class)->execute($credential, $keyPair, [
            'signed_headers' => $signedHeaders,
        ]);
    }
}
