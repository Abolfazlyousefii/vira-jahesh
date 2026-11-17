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

if (! function_exists('get_widget')) {
    function get_widget(object $widget): array
    {
        $url = route('storefront.preview.products');
        $slide = static fn (string $file, string $title) => (object) [
            'image' => 'legacy-shop-theme/img/default/'.$file,
            'link' => $url,
            'title' => $title,
        ];

        return match ($widget->key) {
            'main-slider' => [
                'main_sliders' => collect([
                    $slide('slider1.jpg', 'فروشگاه ویرا'),
                    $slide('slider2.jpg', 'محصولات فروشگاه'),
                ]),
                'mobile_sliders' => collect([
                    $slide('slider3.jpg', 'فروشگاه ویرا'),
                    $slide('slider4.jpg', 'محصولات فروشگاه'),
                ]),
                'index_slider_banners' => collect([
                    $slide('banner1.jpg', 'پیشنهادهای فروشگاه'),
                    $slide('banner2.jpg', 'محصولات جدید'),
                ]),
            ],
            'middle-banners' => [
                'index_middle_banners' => collect([
                    $slide('banner3.jpg', 'فروشگاه ویرا'),
                    $slide('banner4.jpg', 'خرید از فروشگاه'),
                ]),
            ],
            'sevices-sliders' => [
                'sevices_sliders' => collect([
                    $slide('slider10.svg', 'پشتیبانی'),
                    $slide('slider11.svg', 'محصولات'),
                    $slide('slider12.svg', 'خدمات فروشگاه'),
                ]),
            ],
            default => [],
        };
    }
}
