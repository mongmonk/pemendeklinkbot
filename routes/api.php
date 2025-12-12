<?php

use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('telegram')->group(function () {
    Route::post('/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook')->middleware('throttle:5,1');
    Route::get('/set-webhook', [TelegramController::class, 'setWebhook'])->name('telegram.set-webhook');
    Route::get('/webhook-info', [TelegramController::class, 'getWebhookInfo'])->name('telegram.webhook-info');
});