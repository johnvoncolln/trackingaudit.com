<?php

use App\Http\Controllers\Api\TrackingApiController;
use App\Http\Controllers\Webhooks\EasyPostWebhookController;
use App\Http\Middleware\VerifyEasyPostSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/v1/tracking/{apiToken}', [TrackingApiController::class, 'store'])
    ->middleware('throttle:60,1');

Route::post('/webhooks/easypost', EasyPostWebhookController::class)
    ->middleware(VerifyEasyPostSignature::class)
    ->name('webhooks.easypost');
