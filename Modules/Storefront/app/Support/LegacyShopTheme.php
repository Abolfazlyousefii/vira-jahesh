<?php

namespace Modules\Storefront\Support;

use Illuminate\Support\Facades\View;

final class LegacyShopTheme
{
    public function stagedRoot(): string
    {
        return storage_path('app/private/legacy-shop-1.19.8');
    }

    public function ready(): bool
    {
        $root = $this->stagedRoot();

        return is_file($root.'/manifest.json')
            && is_file($root.'/themes/DefaultTheme/src/resources/views/index.blade.php')
            && is_file($root.'/compat/views/layouts/master.blade.php')
            && is_file($root.'/compat/views/index.blade.php')
            && is_file(public_path('legacy-shop-theme/css/main.css'))
            && is_file(public_path('legacy-shop-theme/asset-manifest.json'));
    }

    public function register(): void
    {
        $root = $this->stagedRoot();

        View::addNamespace('front', [
            $root.'/compat/views',
            base_path('Modules/Storefront/resources/views/legacy-overrides'),
            $root.'/themes/DefaultTheme/src/resources/views',
        ]);
    }

    public function ensureAvailable(): void
    {
        abort_unless(config('vira.shop_preview.enabled', false), 404);
        abort_unless($this->ready(), 503, 'قالب هنوز در این نصب آماده نشده است.');
    }
}
