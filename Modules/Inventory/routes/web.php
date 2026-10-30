<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Inventory', 'can:inventory.view'])
    ->get('/panel/inventory', fn () => view('core::module', ['module' => 'Inventory']))
    ->name('panel.inventory');
