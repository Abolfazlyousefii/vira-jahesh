<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Livewire\StorefrontEditor;

Route::middleware(['web', 'auth', 'module.active:WebsiteAdmin', 'website.access'])
    ->prefix('site-admin')
    ->name('website.')
    ->group(function (): void {
        Route::get('/', fn () => view('websiteadmin::dashboard'))->name('dashboard');

        Route::get('/homepage', StorefrontEditor::class)
            ->middleware('module.active:Storefront')
            ->name('homepage');
    });
