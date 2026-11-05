<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Modules\Rentri\Datas\RentriKeyPairData;
use Modules\Rentri\Models\RentriCredential;
use Spatie\QueueableAction\QueueableAction;

/**
 * JWT del pattern ModI ID_AUTH_REST_02, inviato come `Authorization: Bearer <jwt>`.
 *
 * Verificato: firma con la chiave del certificato di interoperabilita' e header `x5c` con il certificato.
 * DA VERIFICARE sullo Swagger demo: claim (`iss` = identificativo del soggetto, `aud`, durata) — vedi config `rentri.jwt`.
 * Ogni chiamata genera un `jti` nuovo (anti-replay): non riusare il token tra tentativi.
 */
class BuildAuthorizationJwtAction
{
    use QueueableAction;

    /**
     * @param  array<string, mixed>  $extraClaims
     */
    public function execute(RentriCredential $credential, RentriKeyPairData $keyPair, array $extraClaims = []): string
    {
        $now = time();

        $claims = [
            'iss' => $credential->subject_identifier,
            'aud' => $credential->environment->audience(),
            'jti' => (string) Str::uuid(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + config()->integer('rentri.jwt.ttl'),
            ...$extraClaims,
        ];

        return JWT::encode($claims, $keyPair->privateKeyPem, $keyPair->algorithm, null, ['x5c' => $keyPair->x5c]);
    }
}
