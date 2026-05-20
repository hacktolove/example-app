<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Webhook\MtnWebhookController;
use Illuminate\Support\Facades\Route;

Route::any('/mtn/wh', MtnWebhookController::class)->name('mtn.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/kpi', [DashboardController::class, 'kpi']);
    Route::get('/dashboard/trend', [DashboardController::class, 'trend']);
});
