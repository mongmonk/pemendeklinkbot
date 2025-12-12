<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

// Redirect routes
Route::get('/', function () {
    return view('welcome');
});

// Redirect route should be the last to avoid conflicts with other routes
Route::get('/preview/{shortCode}', [RedirectController::class, 'preview'])->name('preview')->where('shortCode', '[a-zA-Z0-9_-]+');
Route::get('/{shortCode}', [RedirectController::class, 'redirect'])->name('redirect')->where('shortCode', '[a-zA-Z0-9_-]+');
