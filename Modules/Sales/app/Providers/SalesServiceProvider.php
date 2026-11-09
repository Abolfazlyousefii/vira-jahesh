<?php

namespace Modules\Sales\Providers;

use App\Support\ModuleServiceProvider;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Sales\Models\Sale;

class SalesServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Sales';

    public function boot(): void
    {
        parent::boot();
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            $registry->register('sales.today', 'فروش امروز', 'Sales', 'sales.view', 'core::dashboard.widgets.metric', function (): array {
                $start = now('Asia/Tehran')->startOfDay()->utc();
                $end = now('Asia/Tehran')->addDay()->startOfDay()->utc();

                return ['value' => (int) Sale::where('created_at', '>=', $start)->where('created_at', '<', $end)->sum('total_toman'), 'unit' => 'تومان', 'description' => 'فروش ناخالص امروز تهران، پیش از کسر مرجوعی', 'route' => 'panel.sales', 'label' => 'مشاهدهٔ فاکتورها'];
            }, 10);
        });
    }
}
