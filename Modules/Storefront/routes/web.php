<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Livewire\StorefrontEditor;
use Modules\Storefront\Support\StorefrontSections;

Route::middleware(['web', 'module.active:Storefront'])
    ->get('/', fn (StorefrontSections $sections) => view('storefront::home', [
        'sections' => $sections->all(),
    ]))->name('storefront.home');

Route::middleware([
    'web', 'auth', 'module.active:Core', 'module.active:Storefront',
    'can:panel.access', 'can:storefront.manage',
])->get('/panel/storefront', StorefrontEditor::class)->name('panel.storefront');
