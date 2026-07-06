<?php

use App\Http\Controllers\Api\CheckSubController;
use App\Http\Controllers\Api\SubscribeController;
use App\Http\Controllers\Webhook\MtnWebhookController;
use Illuminate\Support\Facades\Route;

Route::any('/mtn/wh', MtnWebhookController::class)->name('mtn.webhook');

Route::middleware('api.key')->group(function () {
    Route::get('/check-sub', CheckSubController::class)->name('check-sub');
    Route::post('/subscribe', SubscribeController::class)->name('subscribe');
});
