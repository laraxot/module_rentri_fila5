<?php

declare(strict_types=1);

namespace Modules\Rentri\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\Rentri\Providers\RentriServiceProvider;
use Modules\Xot\Tests\XotBaseTestCase;

/**
 * Base dei test del modulo Rentri: sqlite condiviso (come Activity), connessioni `rentri` e `waste` puntate
 * sullo stesso file, migrazioni del modulo applicate se mancano (mai migrate:fresh).
 */
abstract class TestCase extends XotBaseTestCase
{
    use DatabaseTransactions;

    /** @var list<string> */
    protected $connectionsToTransact = ['sqlite', 'rentri', 'waste', 'user'];

    protected function setUp(): void
    {
        $this->refreshApplication();
        $this->defineModuleConnections();
        $this->prepareSharedSqliteForTesting();

        parent::setUp();

        $this->migrateModuleIfNeeded();
    }

    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders(Application $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            RentriServiceProvider::class,
        ];
    }

    private function defineModuleConnections(): void
    {
        foreach (['rentri', 'waste'] as $connection) {
            if (config('database.connections.'.$connection) === null) {
                config(['database.connections.'.$connection => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                    'foreign_key_constraints' => false,
                ]]);
            }
        }
    }

    /**
     * Migrazioni di Rentri e, per i test di mapping, di Waste (modulo da cui Rentri legge FIR e registri).
     */
    private function migrateModuleIfNeeded(): void
    {
        $paths = [];
        if (! Schema::connection('rentri')->hasTable('rentri_transmissions')) {
            $paths[] = \dirname(__DIR__).'/database/migrations';
        }
        $waste = \dirname(__DIR__, 2).'/Waste/database/migrations';
        if (is_dir($waste) && ! Schema::connection('waste')->hasTable('firs')) {
            $paths[] = $waste;
        }
        if ($paths === []) {
            return;
        }

        $this->artisan('migrate', ['--path' => $paths, '--realpath' => true]);
    }
}
