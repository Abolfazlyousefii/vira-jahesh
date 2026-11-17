<?php

use App\Http\Controllers\WorkspaceSelectorController;
use Illuminate\Support\Facades\Route;

// Both management environments share Fortify authentication and the web session.
Route::middleware('auth')
    ->get('/workspace', WorkspaceSelectorController::class)
    ->name('workspace.select');
