<?php

declare(strict_types=1);

namespace Modules\Rentri\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;

/**
 * Registrato da XotBaseServiceProvider::register(): il modulo non ascolta eventi.
 */
class EventServiceProvider extends BaseEventServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
