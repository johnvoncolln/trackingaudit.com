<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::get('/tracking/new', [TrackingController::class, 'showForm'])->name('tracking.form');
    Route::post('/tracking', [TrackingController::class, 'track'])->name('tracking.track');
    Route::get('/tracking/{tracker}', [TrackingController::class, 'show'])->name('tracking.show');
});
