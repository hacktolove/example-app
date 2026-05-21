<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('dashboard/kpi', [DashboardController::class, 'show'])->name('dashboard.kpi');
    Route::get('api/dashboard/kpi', [DashboardController::class, 'kpi']);
    Route::get('api/dashboard/trend', [DashboardController::class, 'trend']);
});

require __DIR__.'/settings.php';
