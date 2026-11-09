<?php

namespace Modules\Crm\Providers;

use App\Support\ModuleServiceProvider;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Crm\Models\FollowUp;

class CrmServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Crm';

    public function boot(): void
    {
        parent::boot();
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            $registry->register('crm.open', 'پیگیری‌های باز', 'Crm', 'crm.view', 'core::dashboard.widgets.metric', fn () => [
                'value' => FollowUp::where('status', 'open')->count(), 'unit' => 'پیگیری', 'description' => 'کارهایی که هنوز انجام نشده‌اند', 'route' => 'panel.crm', 'label' => 'رسیدگی به پیگیری‌ها',
            ]);
        });
    }
}
