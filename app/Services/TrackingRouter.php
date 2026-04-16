<?php

namespace App\Services;

use App\Models\Tracker;
use App\Services\Tracking\TrackerFactory;
use Illuminate\Support\Facades\Auth;

class TrackingRouter
{
    public static function route(array $validated): Tracker
    {
        $tracker = TrackerFactory::make($validated['carrier']);

        return $tracker->track(Auth::user(), $validated);
    }
}
