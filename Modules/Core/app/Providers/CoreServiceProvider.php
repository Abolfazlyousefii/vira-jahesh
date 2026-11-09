<?php

namespace Modules\Core\Providers;

use App\Models\User;
use App\Support\ModuleAccess;
use App\Support\ModuleServiceProvider;
use Modules\Core\Dashboard\WidgetRegistry;
use Spatie\Activitylog\Models\Activity;

class CoreServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Core';

    public function register(): void
    {
        $this->app->singleton(WidgetRegistry::class, function ($app): WidgetRegistry {
            $registry = new WidgetRegistry($app->make(ModuleAccess::class));

            $registry->register(
                'modules', 'وضعیت ماژول‌ها', 'Core', 'panel.access',
                'core::dashboard.widgets.modules',
                fn () => ['modules' => app(ModuleAccess::class)->definitions()], 300
            );
            $registry->register(
                'staff', 'کارکنان مجموعه', 'Core', 'modules.manage',
                'core::dashboard.widgets.staff',
                fn () => ['count' => User::count()], 240
            );
            $registry->register(
                'activities', 'فعالیت‌های اخیر', 'Core', 'modules.manage',
                'core::dashboard.widgets.activities',
                fn () => ['activities' => Activity::latest()->limit(5)->get()], 250
            );
            $registry->register(
                'shortcuts', 'دسترسی سریع', 'Core', 'panel.access',
                'core::dashboard.widgets.shortcuts',
                fn () => ['storefront' => app(ModuleAccess::class)->available('Storefront')], 200
            );

            return $registry;
        });
    }
}
