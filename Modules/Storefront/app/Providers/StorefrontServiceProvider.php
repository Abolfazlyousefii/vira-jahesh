<?php

namespace Modules\Storefront\Providers;

use App\Support\ModuleServiceProvider;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Dashboard\WidgetRegistry;

class StorefrontServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Storefront';

    public function boot(): void
    {
        parent::boot();
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            $registry->register('storefront.published', 'ویترین سایت', 'Storefront', 'storefront.manage', 'core::dashboard.widgets.metric', fn () => [
                'value' => app(ProductDirectory::class)->search('', true)->count(), 'unit' => 'کالای منتشرشده', 'description' => 'کالاهای فعال و منتشرشده؛ خرید اینترنتی هنوز فعال نیست.', 'route' => 'panel.storefront', 'label' => 'مدیریت صفحهٔ اصلی',
            ]);
        });
    }
}
