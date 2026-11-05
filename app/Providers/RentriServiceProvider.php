<?php

declare(strict_types=1);

namespace Modules\Rentri\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Rentri\Actions\PollPendingRentriTransactionsAction;
use Modules\Xot\Providers\XotBaseServiceProvider;
use Override;

/**
 * Service provider del modulo Rentri: config `rentri.*` e poll pianificato delle transazioni asincrone.
 */
class RentriServiceProvider extends XotBaseServiceProvider
{
    public string $name = 'Rentri';

    protected string $module_dir = __DIR__;

    protected string $module_ns = __NAMESPACE__;

    #[Override]
    public function boot(): void
    {
        parent::boot();

        $this->registerSchedule();
    }

    #[Override]
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'config/config.php') => config_path('rentri.php'),
        ], 'config');

        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), 'rentri');
    }

    /**
     * Ogni minuto: esito delle trasmissioni `submitted` (PollPendingRentriTransactionsAction).
     */
    protected function registerSchedule(): void
    {
        if (! config()->boolean('rentri.polling.enabled')) {
            return;
        }

        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->call(static fn (): int => app(PollPendingRentriTransactionsAction::class)->execute())
                ->name('rentri:poll-pending-transactions')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }
}
