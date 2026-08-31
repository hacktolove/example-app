<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Ivr\IvrController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('dashboard/kpi', [DashboardController::class, 'show'])->name('dashboard.kpi');
    Route::get('api/dashboard/kpi', [DashboardController::class, 'kpi']);
    Route::get('api/dashboard/trend', [DashboardController::class, 'trend']);

    Route::get('ivr', [IvrController::class, 'index'])->name('ivr.index');
    Route::post('ivr', [IvrController::class, 'store'])->name('ivr.store');
    Route::put('ivr/order', [IvrController::class, 'reorder'])->name('ivr.reorder');
    Route::delete('ivr/{ivrAudioFile}', [IvrController::class, 'destroy'])->name('ivr.destroy');
});

require __DIR__.'/settings.php';
