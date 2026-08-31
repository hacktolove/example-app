<?php

use App\Http\Controllers\Api\CheckSubController;
use App\Http\Controllers\Api\SubscribeController;
use App\Http\Controllers\Wave\ChargeController;
use App\Http\Controllers\Wave\GetBalanceController;
use App\Http\Controllers\Wave\LocationController;
use App\Http\Controllers\Webhook\MtnWebhookController;
use Illuminate\Support\Facades\Route;

Route::any('/mtn/wh', MtnWebhookController::class)->name('mtn.webhook');

Route::middleware('api.key')->group(function () {
    Route::get('/check-sub', CheckSubController::class)->name('check-sub');
    Route::post('/subscribe', SubscribeController::class)->name('subscribe');
});

Route::prefix('v1/wave')->group(function () {
    Route::post('/balance', GetBalanceController::class)->name('wave.balance');
    Route::post('/charge', ChargeController::class)->name('wave.charge');
    Route::post('/location', LocationController::class)->name('wave.location');
});
