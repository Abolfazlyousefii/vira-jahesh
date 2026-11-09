<?php

use Illuminate\Support\Facades\Route;
use Modules\Crm\Livewire\FollowUpsPage;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Crm', 'can:crm.view'])
    ->get('/panel/crm', FollowUpsPage::class)
    ->name('panel.crm');
