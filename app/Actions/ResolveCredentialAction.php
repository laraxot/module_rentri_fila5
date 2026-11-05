<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Illuminate\Database\Eloquent\Builder;
use Modules\Rentri\Exceptions\RentriCredentialException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;

/**
 * Credenziale RENTRI attiva e non scaduta per un'unita' locale. Con piu' credenziali valide preferisce
 * l'ambiente `rentri.default_environment`, poi la piu' recente.
 */
class ResolveCredentialAction
{
    use QueueableAction;

    public function execute(OperatorSite $site): RentriCredential
    {
        $siteId = $site->getKey();
        $siteId = is_numeric($siteId) ? (int) $siteId : 0;
        $preferred = config()->string('rentri.default_environment');

        $credential = RentriCredential::query()
            ->where('operator_site_id', $siteId)
            ->where('is_active', true)
            ->where(static function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByRaw('case when environment = ? then 0 else 1 end', [$preferred])
            ->orderByDesc('id')
            ->first();

        if (! $credential instanceof RentriCredential) {
            throw RentriCredentialException::notFoundForSite($siteId);
        }

        return $credential;
    }
}
