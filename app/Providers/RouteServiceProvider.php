<?php

declare(strict_types=1);

namespace Modules\Rentri\Providers;

use Modules\Xot\Providers\XotBaseRouteServiceProvider;

class RouteServiceProvider extends XotBaseRouteServiceProvider
{
    public string $name = 'Rentri';

    protected string $moduleNamespace = 'Modules\Rentri\Http\Controllers';

    protected string $module_dir = __DIR__;

    protected string $module_ns = __NAMESPACE__;
}
