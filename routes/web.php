<?php

use App\Http\Controllers\ImportTrackingController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/tracking/new', [TrackingController::class, 'create'])->name('tracking.form');
    Route::post('/tracking', [TrackingController::class, 'store'])->name('tracking.track');
    Route::post('/tracking/import', [ImportTrackingController::class, 'create'])->name('tracking.import');
    Route::get('/tracking/template', [ImportTrackingController::class, 'downloadTemplate'])->name('tracking.template');
    Route::get('/tracking/{tracker}', [TrackingController::class, 'show'])->name('tracking.show');
    Route::post('/tracking/{tracker}/update', [TrackingController::class, 'update'])->name('tracking.update');
    Route::get('/settings', function () {
        return view('settings');
    })->name('settings');
});
