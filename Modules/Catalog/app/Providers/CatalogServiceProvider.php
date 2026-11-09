<?php

namespace Modules\Catalog\Providers;

use App\Support\ModuleServiceProvider;
use Modules\Catalog\Models\Product;
use Modules\Core\Dashboard\WidgetRegistry;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Catalog';

    public function boot(): void
    {
        parent::boot();
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            $registry->register('catalog.count', 'کالاهای فعال', 'Catalog', 'catalog.view', 'core::dashboard.widgets.metric', fn () => [
                'value' => Product::where('is_active', true)->count(), 'unit' => 'کالا', 'description' => 'کالاهای فعال در فهرست؛ موجودی را در انبار بررسی کنید.', 'route' => 'panel.catalog', 'label' => 'مدیریت کالاها',
            ]);
        });
    }
}
