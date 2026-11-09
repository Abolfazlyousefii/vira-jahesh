<?php

use Illuminate\Support\Facades\Route;
use Modules\Pos\Livewire\PosPage;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Pos', 'can:pos.view'])
    ->get('/panel/pos', PosPage::class)
    ->name('panel.pos');
