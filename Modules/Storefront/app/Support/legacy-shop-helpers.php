<?php

// Limited compatibility helpers for the privately staged original DefaultTheme.
// Do not load the original Laravel Shop helpers; they reference unrelated models.
if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (str_contains($path, '..') || str_contains($path, ':') || str_starts_with($path, '/')) {
            return '';
        }

        return asset('legacy-shop-theme/'.$path);
    }
}

if (! function_exists('option')) {
    function option(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'site_title', 'info_site_title' => config('vira.brand', 'ویرا'),
            'info_footer_text' => 'ویرا — یکپارچه با جهش',
            'info_short_description' => 'فروشگاه ویرا',
            'allow_indexing_main_page' => 'off',
            default => $default,
        };
    }
}
