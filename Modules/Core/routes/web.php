<?php

use App\Livewire\FoundationDashboard;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'module.active:Core', 'can:panel.access'])
    ->prefix('panel')->name('panel.')->group(function (): void {
        Route::get('/', FoundationDashboard::class)->name('dashboard');
        Route::get('/modules', fn (ModuleAccess $modules) => view('core::modules', [
            'modules' => $modules->definitions(),
        ]))->middleware('can:modules.manage')->name('modules');
    });
