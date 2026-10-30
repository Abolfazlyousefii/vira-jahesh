<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Customers', 'can:customers.view'])
    ->get('/panel/customers', fn () => view('core::module', ['module' => 'Customers']))
    ->name('panel.customers');
