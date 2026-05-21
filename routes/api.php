<?php

use App\Http\Controllers\Webhook\MtnWebhookController;
use Illuminate\Support\Facades\Route;

Route::any('/mtn/wh', MtnWebhookController::class)->name('mtn.webhook');
