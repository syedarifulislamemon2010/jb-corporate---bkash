<?php

use App\Http\Controllers\Api\CbsResponseCallbackController;
use App\Http\Controllers\Api\TestTokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Production CBS Host-to-Host Asynchronous Callback Endpoint
|--------------------------------------------------------------------------
| Primary bank-to-portal integration route authenticated via X-CBS-API-Key.
*/
Route::post('/cbs/response-callback', [CbsResponseCallbackController::class, 'store'])
    ->middleware(['api.cbs.auth', 'throttle:120,1'])
    ->name('api.cbs.response-callback');

/*
|--------------------------------------------------------------------------
| Non-Production Testing Endpoints (Dev / Staging / Postman only)
|--------------------------------------------------------------------------
| Completely disabled in production environment.
*/
if (!app()->environment('production')) {
    // 1. Issue Sanctum token for test user
    Route::post('/test-auth/token', [TestTokenController::class, 'issueToken'])
        ->name('api.test-auth.token');

    // 2. Token-protected CBS callback test mirror (reuses CbsResponseCallbackController::store)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/test-auth/cbs/response-callback', [CbsResponseCallbackController::class, 'store'])
            ->name('api.test-auth.cbs-callback');
    });
}
