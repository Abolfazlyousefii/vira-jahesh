<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Livewire\ProductsPage;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Catalog', 'can:catalog.view'])
    ->group(function (): void {
        Route::get('/panel/catalog/{mode}', ProductsPage::class)
            ->where('mode', 'create')
            ->name('panel.catalog.create');

        Route::get('/panel/catalog', ProductsPage::class)
            ->name('panel.catalog');
    });
