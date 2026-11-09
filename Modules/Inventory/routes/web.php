<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Livewire\InventoryPage;
use Modules\Inventory\Livewire\StockDocumentsPage;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Inventory', 'can:inventory.view'])
    ->get('/panel/inventory/documents', StockDocumentsPage::class)->name('panel.inventory.documents');

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Inventory', 'can:inventory.view'])
    ->get('/panel/inventory', InventoryPage::class)
    ->name('panel.inventory');
