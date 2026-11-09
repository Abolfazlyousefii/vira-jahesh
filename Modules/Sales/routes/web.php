<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\ExportSalesReportController;
use Modules\Sales\Livewire\ReportsPage;
use Modules\Sales\Livewire\SalesPage;

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Sales', 'can:sales.view'])->group(function () {
    Route::get('/panel/reports', ReportsPage::class)->name('panel.reports');
    Route::get('/panel/reports/export', ExportSalesReportController::class)->name('panel.reports.export');
});

Route::middleware(['web', 'auth', 'can:panel.access', 'module.active:Sales', 'can:sales.view'])
    ->get('/panel/sales', SalesPage::class)
    ->name('panel.sales');
