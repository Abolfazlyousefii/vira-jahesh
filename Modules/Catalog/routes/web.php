<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Catalog', 'can:catalog.view'])
    ->get('/panel/catalog', fn () => view('core::module', ['module' => 'Catalog']))
    ->name('panel.catalog');
