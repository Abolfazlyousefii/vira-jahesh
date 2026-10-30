<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Pos', 'can:pos.view'])
    ->get('/panel/pos', fn () => view('core::module', ['module' => 'Pos']))
    ->name('panel.pos');
