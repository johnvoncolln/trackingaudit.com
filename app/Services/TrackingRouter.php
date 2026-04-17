<?php

namespace App\Services;

use App\Models\Tracker;
use App\Services\Tracking\TrackerFactory;
use Illuminate\Support\Facades\Auth;

class TrackingRouter
{
    public static function route(array $validated): Tracker
    {
        $carrier = $validated['carrier'] ?? CarrierDetector::detect($validated['tracking_number']);

        $tracker = TrackerFactory::make($carrier);

        return $tracker->track(Auth::user(), array_merge($validated, [
            'carrier' => $carrier instanceof \App\Enums\Carrier ? $carrier->value : $carrier,
        ]));
    }
}
