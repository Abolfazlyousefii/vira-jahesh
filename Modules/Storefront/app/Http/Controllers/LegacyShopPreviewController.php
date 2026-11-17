<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Storefront\Support\LegacyShopTheme;

final class LegacyShopPreviewController extends Controller
{
    public function index(LegacyShopTheme $theme, ProductDirectory $directory)
    {
        $theme->ensureAvailable();

        return view('front::index', [
            ...$this->layoutData(),
            'widgets' => collect([(object) ['key' => 'products-default-block']]),
            'previewProducts' => $directory->search('', true)->limit(12)->get(),
        ]);
    }

    public function products(Request $request, LegacyShopTheme $theme, ProductDirectory $directory)
    {
        $theme->ensureAvailable();
        $search = $request->validate(['q' => 'nullable|string|max:160'])['q'] ?? '';

        return view('front::catalog.index', [
            ...$this->layoutData(),
            'products' => $directory->search($search, true)->paginate(12)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function product(int $id, LegacyShopTheme $theme, ProductDirectory $directory)
    {
        $theme->ensureAvailable();
        $product = $directory->search('', true)->findOrFail($id);

        return view('front::catalog.show', [
            ...$this->layoutData(),
            'product' => $product,
            'siblings' => $directory->publicSiblings($product),
        ]);
    }

    private function layoutData(): array
    {
        return [
            'current_local' => ['direction' => 'rtl'],
            'title' => 'فروشگاه ویرا',
            'menus' => collect(),
            'cart' => null,
            'footer_links' => [],
            'links' => collect(),
        ];
    }
}
