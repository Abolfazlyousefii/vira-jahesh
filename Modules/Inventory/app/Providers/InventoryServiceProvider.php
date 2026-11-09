<?php

namespace Modules\Inventory\Providers;

use App\Support\ModuleServiceProvider;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Inventory\Models\StockBalance;

class InventoryServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Inventory';

    public function boot(): void
    {
        parent::boot();
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            $registry->register('inventory.units', 'موجودی انبارها', 'Inventory', 'inventory.view', 'core::dashboard.widgets.metric', fn () => [
                'value' => (int) StockBalance::sum('quantity'), 'unit' => 'عدد', 'description' => 'جمع تعداد کالاها در تمام انبارها', 'route' => 'panel.inventory', 'label' => 'مشاهدهٔ موجودی و گردش',
            ]);
        });
    }
}
