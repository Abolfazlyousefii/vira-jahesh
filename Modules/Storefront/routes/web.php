<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Storefront\Livewire\StorefrontEditor;
use Modules\Storefront\Support\StorefrontSections;

Route::middleware(['web', 'module.active:Storefront'])
    ->get('/', fn (StorefrontSections $sections) => view('storefront::home', [
        'sections' => $sections->all(),
    ]))->name('storefront.home');

Route::middleware(['web', 'module.active:Storefront'])->group(function (): void {
    Route::get('/shop', function (Request $request, ProductDirectory $directory) {
        $search = $request->validate(['q' => 'nullable|string|max:160'])['q'] ?? '';

        return view('storefront::shop', ['product' => null, 'search' => $search, 'products' => $directory->search($search, true)->paginate(12)->withQueryString()]);
    })->name('storefront.shop');
    Route::get('/shop/{id}', function (int $id, ProductDirectory $directory) {
        $product = $directory->search('', true)->findOrFail($id);

        return view('storefront::shop', ['product' => $product, 'siblings' => $directory->publicSiblings($product)]);
    })->whereNumber('id')->name('storefront.product');
});

Route::middleware([
    'web', 'auth', 'module.active:Core', 'module.active:Storefront',
    'can:panel.access', 'can:storefront.manage',
])->get('/panel/storefront', StorefrontEditor::class)->name('panel.storefront');
