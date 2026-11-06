<?php

declare(strict_types=1);

namespace Modules\Rentri\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestratore Rentri. Le credenziali non si seminano (richiedono un certificato reale caricato dal pannello);
 * le trasmissioni sono un log prodotto dalle Actions.
 */
class RentriDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RentriCodeSeeder::class,
            RentriPermissionSeeder::class,
        ]);
    }
}
