<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\Livewire\CustomerProfilePage;
use Modules\Customers\Livewire\CustomersPage;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Customers', 'can:customers.view'])
    ->get('/panel/customers/{customerId}', CustomerProfilePage::class)->whereNumber('customerId')->name('panel.customers.show');

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Customers', 'can:customers.view'])
    ->get('/panel/customers', CustomersPage::class)
    ->name('panel.customers');
