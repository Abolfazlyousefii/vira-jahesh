<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Crm', 'can:crm.view'])
    ->get('/panel/crm', fn () => view('core::module', ['module' => 'Crm']))
    ->name('panel.crm');
