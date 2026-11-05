<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Exceptions\RentriRequestException;
use Modules\Rentri\Models\RentriCode;
use Modules\Rentri\Models\RentriCredential;
use Spatie\QueueableAction\QueueableAction;

/**
 * Scarica una lista di codifiche RENTRI (`GET /codifiche/v1.0/lookup/{lista}`, DA VERIFICARE) e la salva in `rentri_codes`.
 * Nomi dei campi codice/descrizione in `rentri.code_lists` (DA VERIFICARE). Restituisce il numero di voci salvate.
 */
class SyncRentriCodesAction
{
    use QueueableAction;

    public function execute(RentriCredential $credential, string $list): int
    {
        $exchange = app(SendRentriRequestAction::class)->exchange($credential, new RentriRequestData(
            service: RentriServiceEnum::CODIFICHE,
            method: 'GET',
            path: str_replace('{list}', rawurlencode($list), config()->string('rentri.paths.codes_lookup')),
        ));

        $response = $exchange->response;
        if (! $response instanceof RentriResponseData || ! $response->successful()) {
            throw RentriRequestException::fromTransmission($exchange->transmission, $response?->problem());
        }

        $codeKey = config()->string('rentri.code_lists.code_key');
        $descriptionKey = config()->string('rentri.code_lists.description_key');
        $count = 0;

        foreach ($response->body as $item) {
            if (! \is_array($item) || ! isset($item[$codeKey]) || ! \is_scalar($item[$codeKey])) {
                continue;
            }
            $description = $item[$descriptionKey] ?? '';

            RentriCode::query()->updateOrCreate(
                ['list' => $list, 'code' => (string) $item[$codeKey]],
                ['description' => \is_scalar($description) ? (string) $description : '', 'payload' => $item],
            );
            $count++;
        }

        return $count;
    }
}
