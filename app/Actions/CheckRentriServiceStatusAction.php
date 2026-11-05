<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Carbon\CarbonImmutable;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriServiceStatusData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\RentriServiceHealthEnum;
use Modules\Rentri\Models\RentriCredential;
use Spatie\QueueableAction\QueueableAction;

/**
 * Interroga l'endpoint di stato di un servizio RENTRI (`rentri.paths.service_status`, DA VERIFICARE: `GET /{servizio}/v1.0/status`).
 * 200 => ok (warning se il body riporta `status: Warning`), altrimenti error. Non scrive su rentri_transmissions:
 * e' un controllo di salute, non una trasmissione.
 */
class CheckRentriServiceStatusAction
{
    use QueueableAction;

    public function execute(RentriCredential $credential, RentriServiceEnum $service): RentriServiceStatusData
    {
        try {
            $response = app(PerformRentriRequestAction::class)->execute(
                $credential,
                new RentriRequestData($service, 'GET', config()->string('rentri.paths.service_status')),
            );
        } catch (\Throwable $exception) {
            return new RentriServiceStatusData($service, RentriServiceHealthEnum::ERROR, null, $exception->getMessage(), CarbonImmutable::now());
        }

        $statusText = $response->string('status');
        $health = match (true) {
            ! $response->successful() => RentriServiceHealthEnum::ERROR,
            $statusText !== null && strcasecmp($statusText, 'warning') === 0 => RentriServiceHealthEnum::WARNING,
            default => RentriServiceHealthEnum::OK,
        };

        return new RentriServiceStatusData(
            service: $service,
            health: $health,
            httpStatus: $response->status,
            message: $response->successful() ? ($response->string('description') ?? $statusText) : $response->errorSummary(),
            checkedAt: CarbonImmutable::now(),
        );
    }
}
