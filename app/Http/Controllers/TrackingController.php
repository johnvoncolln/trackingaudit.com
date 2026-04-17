<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingRequest;
use App\Models\Tracker;
use App\Services\Tracking\TrackerFactory;
use App\Services\TrackingRouter;

class TrackingController extends Controller
{
    public function index()
    {
        return view('tracking.index');
    }

    public function create()
    {
        return view('tracking.form');
    }

    public function show(Tracker $tracker)
    {
        $tracker->load('trackerData');

        return view('tracking.show', compact('tracker'));
    }

    public function update(Tracker $tracker)
    {
        try {
            $trackerService = TrackerFactory::make($tracker->carrier);
            $tracker = $trackerService->update($tracker);

            return redirect()->route('tracking.show', $tracker)
                ->with('flash.banner', 'Tracking information updated successfully.')
                ->with('flash.bannerStyle', 'success');

        } catch (\Exception $e) {
            return redirect()->route('tracking.show', $tracker)
                ->with('flash.banner', 'Unable to update tracking details. Please try again.')
                ->with('flash.bannerStyle', 'danger');
        }
    }

    public function store(TrackingRequest $request)
    {
        $validated = $request->validated();

        try {
            $tracker = TrackingRouter::route($validated);

            return view('tracking.show', ['tracker' => $tracker]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to retrieve tracking details. Please try again.']);
        }
    }
}
