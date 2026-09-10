<?php

use App\Http\Controllers\BmwController;
use App\Http\Middleware\RequireApiKey;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['app' => 'bmw-lock-checker', 'endpoints' => ['GET /api/bmw/vehicles', 'GET /api/bmw/status', 'POST /api/bmw/check', 'POST /api/bmw/lock (501)']]));

// Protected by a shared secret (BMW_API_KEY). Stateless JSON, so CSRF does not apply.
Route::prefix('api/bmw')->middleware(RequireApiKey::class)->withoutMiddleware(ValidateCsrfToken::class)->group(function () {
    Route::get('vehicles', [BmwController::class, 'vehicles']);
    Route::get('status', [BmwController::class, 'status']);
    Route::match(['get', 'post'], 'check', [BmwController::class, 'check']);
    Route::post('lock', [BmwController::class, 'lock']); // always 501 — see README
});
