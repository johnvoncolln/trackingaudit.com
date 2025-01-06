<?php

namespace App\Http\Controllers;

use App\Models\Tracker;
use App\Models\TrackerData;
use App\Rules\UpsTrackingNumber;
use App\Services\UpsService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    protected $upsService;

    public function __construct(UpsService $upsService)
    {
        $this->upsService = $upsService;
    }

    public function index()
    {
        $trackers = Tracker::latest()->paginate(10);
        return view('tracking.index', compact('trackers'));
    }

    public function showForm()
    {
        return view('tracking.form');
    }

    public function show(Tracker $tracker)
    {
        return view('tracking.show', compact('tracker'));
    }

    public function track(Request $request)
    {
        $request->validate([
            'tracking_number' => ['required', 'string', 'max:50', new UpsTrackingNumber],
            'reference_id' => ['nullable', 'string', 'max:50'],
            'reference_name' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            // Create or find the tracker
            $tracker = Tracker::firstOrCreate(
                ['tracking_number' => $request->tracking_number],
                [
                    'carrier' => 'UPS',
                    'reference_id' => $request->reference_id,
                    'reference_name' => $request->reference_name
                ]
            );

            // Fetch tracking details from UPS
            $trackingInfo = $this->upsService->fetchTrackingDetails($request->tracking_number);

            // Update tracker with latest info
            if (isset($trackingInfo['trackResponse']['shipment'][0]['package'][0])) {
                $package = $trackingInfo['trackResponse']['shipment'][0]['package'][0];
                $latestActivity = $package['activity'][0] ?? null;

                if ($latestActivity) {
                    $tracker->update([
                        'status' => $latestActivity['status']['description'] ?? null,
                        'status_time' => isset($latestActivity['date'], $latestActivity['time']) 
                            ? date('Y-m-d H:i:s', strtotime($latestActivity['date'] . ' ' . $latestActivity['time']))
                            : null,
                        'location' => isset($latestActivity['location']) 
                            ? implode(', ', array_filter([
                                $latestActivity['location']['address']['city'] ?? null,
                                $latestActivity['location']['address']['stateProvince'] ?? null,
                                $latestActivity['location']['address']['countryCode'] ?? null
                            ]))
                            : null
                    ]);
                }
            }

            // Create or update tracker data
            TrackerData::updateOrCreate(
                ['trackers_id' => $tracker->id],
                ['data' => json_encode($trackingInfo)]
            );

            return view('tracking.results', [
                'trackingInfo' => $trackingInfo,
                'tracker' => $tracker
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to retrieve tracking details. Please try again.']);
        }
    }
}
