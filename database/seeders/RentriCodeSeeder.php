<?php

declare(strict_types=1);

namespace Modules\Rentri\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Rentri\Exceptions\RentriException;
use Modules\Rentri\Models\RentriCode;

/**
 * Semina in `rentri_codes` solo i codici di `rentri.codes.acceptance` (lista `tipo_accettazione`),
 * per avere la cache non vuota prima della prima SyncRentriCodesAction. Solo "A" e' verificato; P e R vanno
 * confermati sullo Swagger demo e saranno comunque sovrascritti dalla sincronizzazione.
 */
class RentriCodeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config()->array('rentri.codes.acceptance') as $value => $code) {
            if (! \is_string($code) || ! \is_string($value)) {
                continue;
            }

            RentriCode::query()->updateOrCreate(
                ['list' => 'tipo_accettazione', 'code' => $code],
                ['description' => RentriException::translate('rentri::rentri_code.seed.acceptance.'.$value), 'payload' => ['source' => 'seeder']],
            );
        }
    }
}
