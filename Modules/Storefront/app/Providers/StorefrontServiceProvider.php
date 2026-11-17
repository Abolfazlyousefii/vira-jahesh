<?php

namespace Modules\Storefront\Providers;

use App\Support\ModuleAccess;
use App\Support\ModuleServiceProvider;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Storefront\Support\LegacyShopTheme;

class StorefrontServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Storefront';

    public function boot(): void
    {
        parent::boot();

        $theme = app(LegacyShopTheme::class);
        if (config('vira.shop_preview.enabled', false) && $theme->ready()) {
            require_once __DIR__.'/../Support/legacy-shop-helpers.php';
            $theme->register();
            $this->loadTranslationsFrom($theme->stagedRoot().'/themes/DefaultTheme/src/resources/lang', 'front');
        }
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            if (! app(ModuleAccess::class)->available('WebsiteAdmin')) {
                return;
            }

            $registry->register('storefront.published', 'ویترین سایت', 'Storefront', 'storefront.manage', 'core::dashboard.widgets.metric', fn () => [
                'value' => app(ProductDirectory::class)->search('', true)->count(), 'unit' => 'کالای منتشرشده', 'description' => 'کالاهای فعال و منتشرشده؛ خرید اینترنتی هنوز فعال نیست.', 'route' => 'panel.storefront', 'label' => 'مدیریت صفحهٔ اصلی',
            ]);
        });
    }
}
