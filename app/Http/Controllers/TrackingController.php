<?php

namespace App\Http\Controllers;

use App\Models\Tracker;
use App\Models\TrackerData;
use App\Rules\UpsTrackingNumber;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Writer;
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
        $tracker->load('trackerData');
        return view('tracking.show', compact('tracker'));
    }

    public function update(Tracker $tracker)
    {
        try {
            // Fetch fresh tracking details from UPS
            $trackingInfo = $this->upsService->fetchTrackingDetails($tracker->tracking_number);

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

            // Update tracker data
            TrackerData::updateOrCreate(
                ['trackers_id' => $tracker->id],
                ['data' => $trackingInfo]
            );

            return redirect()->route('tracking.show', $tracker)
                ->with('flash.banner', 'Tracking information updated successfully.')
                ->with('flash.bannerStyle', 'success');

        } catch (\Exception $e) {
            return redirect()->route('tracking.show', $tracker)
                ->with('flash.banner', 'Unable to update tracking details. Please try again.')
                ->with('flash.bannerStyle', 'danger');
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'], // 2MB max
        ]);

        try {
            $csv = Reader::createFromPath($request->file('csv_file')->getPathname());
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());

            if (count($records) > 500) {
                return back()->withErrors(['csv_file' => 'CSV file contains more than 500 records.']);
            }

            // Validate required columns
            $headers = $csv->getHeader();
            if (!in_array('tracking_number', $headers)) {
                return back()->withErrors(['csv_file' => 'CSV file must contain a tracking_number column.']);
            }

            $validRecords = 0;
            $failedRecords = 0;

            foreach ($records as $record) {
                $validator = Validator::make($record, [
                    'tracking_number' => ['required', 'string', 'max:50', new UpsTrackingNumber],
                    'reference_id' => ['nullable', 'string', 'max:50'],
                    'reference_name' => ['nullable', 'string', 'max:100'],
                    'reference_data' => ['nullable', 'string'],
                    'recipient_name' => ['nullable', 'string', 'max:100'],
                    'recipient_email' => ['nullable', 'email', 'max:100'],
                ]);

                if ($validator->fails()) {
                    $failedRecords++;
                    continue;
                }

                Tracker::create([
                    'user_id' => $request->user()->id,
                    'carrier' => 'UPS',
                    'tracking_number' => $record['tracking_number'],
                    'reference_id' => $record['reference_id'] ?? null,
                    'reference_name' => $record['reference_name'] ?? null,
                    'reference_data' => isset($record['reference_data']) ? json_decode($record['reference_data'], true) : null,
                    'recipient_name' => $record['recipient_name'] ?? null,
                    'recipient_email' => $record['recipient_email'] ?? null,
                ]);

                $validRecords++;
            }

            $message = "Successfully imported {$validRecords} tracking numbers.";
            if ($failedRecords > 0) {
                $message .= " {$failedRecords} records failed validation.";
            }

            return redirect()->route('tracking.index')
                ->with('flash.banner', $message)
                ->with('flash.bannerStyle', $failedRecords > 0 ? 'warning' : 'success');

        } catch (\Exception $e) {
            return back()->withErrors(['csv_file' => 'Error processing CSV file: ' . $e->getMessage()]);
        }
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
                    'user_id' => $request->user()->id,
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

            TrackerData::updateOrCreate(
                ['trackers_id' => $tracker->id],
                ['data' => $trackingInfo]
            );

            return view('tracking.show', ['tracker' => $tracker]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to retrieve tracking details. Please try again.']);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'tracking_number',
            'reference_id',
            'reference_name',
            'reference_data',
            'recipient_name',
            'recipient_email'
        ];

        $csv = Writer::createFromString('');
        $csv->insertOne($headers);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tracking-import-template.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        return response((string) $csv, 200, $headers);
    }
}
