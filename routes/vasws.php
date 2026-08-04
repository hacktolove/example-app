<?php

use App\Http\Controllers\Vasws\DisplayAllController;
use App\Http\Controllers\Vasws\DisplayServicesController;
use App\Http\Controllers\Vasws\HistoryController;
use App\Http\Controllers\Vasws\RemoveAllController;
use App\Http\Controllers\Vasws\RemoveController;
use App\Http\Controllers\Vasws\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::prefix('vasws')->middleware('vasws.auth')->group(function () {
    Route::get('/displayall', DisplayAllController::class)->name('vasws.displayall');
    Route::get('/displayservices', DisplayServicesController::class)->name('vasws.displayservices');
    Route::get('/subscribe', SubscribeController::class)->name('vasws.subscribe');
    Route::get('/remove', RemoveController::class)->name('vasws.remove');
    Route::get('/removeall', RemoveAllController::class)->name('vasws.removeall');
    Route::get('/history', HistoryController::class)->name('vasws.history');
});
