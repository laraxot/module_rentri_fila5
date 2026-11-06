<?php

declare(strict_types=1);

namespace Modules\Rentri\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permessi controllati dalle policy di Rentri (`{modello}.{abilita'}`), guard `web`. Idempotente.
 * L'assegnazione ai ruoli operativi la fa il prodotto (in Roosko: RooskoPermissionSeeder).
 */
class RentriPermissionSeeder extends Seeder
{
    public const array PERMISSIONS = [
        'rentri_credential' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'rentri_code' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'rentri_transmission' => ['viewAny', 'view', 'retry', 'sign'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $model => $abilities) {
            foreach ($abilities as $ability) {
                Permission::findOrCreate($model.'.'.$ability, 'web');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
