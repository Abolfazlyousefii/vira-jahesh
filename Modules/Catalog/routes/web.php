<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Livewire\ProductsPage;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Catalog', 'can:catalog.view'])
    ->get('/panel/catalog', ProductsPage::class)
    ->name('panel.catalog');
