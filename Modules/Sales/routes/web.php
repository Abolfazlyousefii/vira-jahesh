<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Sales', 'can:sales.view'])
    ->get('/panel/sales', fn () => view('core::module', ['module' => 'Sales']))
    ->name('panel.sales');
