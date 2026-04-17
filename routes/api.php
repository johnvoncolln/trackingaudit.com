<?php

use App\Http\Controllers\Api\TrackingApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/v1/tracking/{apiToken}', [TrackingApiController::class, 'store'])
    ->middleware('throttle:60,1');
