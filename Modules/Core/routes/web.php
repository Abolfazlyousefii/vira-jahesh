<?php

use App\Livewire\FoundationDashboard;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;
use Modules\Core\Livewire\BusinessSettingsPage;
use Modules\Core\Livewire\StaffPage;

Route::middleware(['web', 'auth', 'module.active:Core', 'can:panel.access'])
    ->prefix('panel')->name('panel.')->group(function (): void {
        Route::get('/', FoundationDashboard::class)->name('dashboard');
        Route::get('/settings', BusinessSettingsPage::class)->middleware('can:settings.manage')->name('settings');
        Route::get('/staff', StaffPage::class)->middleware('can:users.manage')->name('staff');
        Route::get('/modules', fn (ModuleAccess $modules) => view('core::modules', [
            'modules' => $modules->definitions(),
        ]))->middleware('can:modules.manage')->name('modules');
    });
