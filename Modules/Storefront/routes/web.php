<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'module.active:Storefront'])
    ->get('/', fn () => view('storefront::home'))->name('storefront.home');
