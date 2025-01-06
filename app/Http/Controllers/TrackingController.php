<?php

namespace App\Http\Controllers;

use App\Services\UpsService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    protected $upsService;

    public function __construct(UpsService $upsService)
    {
        $this->upsService = $upsService;
    }

    public function showForm()
    {
        return view('tracking.form');
    }

    public function track(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:50',
        ]);

        try {
            $trackingInfo = $this->upsService->fetchTrackingDetails($request->tracking_number);

            return view('tracking.results', ['trackingInfo' => $trackingInfo]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to retrieve tracking details. Please try again.']);
        }
    }
}
