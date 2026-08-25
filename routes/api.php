<?php

use App\Http\Controllers\Api\CbsResponseCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/cbs/response-callback', [CbsResponseCallbackController::class, 'store'])
    ->middleware(['api.cbs.auth'])
    ->name('api.cbs.response-callback');
