<?php

namespace App\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\Tracker;
use App\Models\TrackerData;
use App\Models\User;
use App\Services\Api\FedexApiService;
use Exception;

class FedexTracker implements CarrierTracker
{
    public function __construct(protected FedexApiService $fedexService) {}

    public function track(User $user, array $data): Tracker
    {
        $trackingInfo = $this->fedexService->fetchTrackingDetails($data['tracking_number']);

        $tracker = Tracker::firstOrCreate(
            ['tracking_number' => $data['tracking_number']],
            [
                'user_id' => $user->id,
                'carrier' => $data['carrier'],
                'reference_id' => $data['reference_id'] ?? null,
                'reference_name' => $data['reference_name'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_email' => $data['recipient_email'] ?? null,
            ]
        );

        $this->applyResponseToTracker($tracker, $trackingInfo);

        TrackerData::updateOrCreate(
            ['trackers_id' => $tracker->id],
            ['data' => $trackingInfo]
        );

        return $tracker->refresh();
    }

    public function update(Tracker $tracker): Tracker
    {
        $trackingInfo = $this->fedexService->fetchTrackingDetails($tracker->tracking_number);

        $this->applyResponseToTracker($tracker, $trackingInfo);

        TrackerData::updateOrCreate(
            ['trackers_id' => $tracker->id],
            ['data' => $trackingInfo]
        );

        return $tracker->refresh();
    }

    protected function applyResponseToTracker(Tracker $tracker, array $trackingInfo): void
    {
        $trackResult = $trackingInfo['output']['completeTrackResults'][0]['trackResults'][0] ?? null;

        if (! $trackResult) {
            throw new Exception('Unexpected FedEx tracking response.');
        }

        $latestScan = $trackResult['scanEvents'][0] ?? null;
        $latestStatus = $trackResult['latestStatusDetail'] ?? null;

        $rawStatus = $latestStatus['description']
            ?? $latestStatus['statusByLocale']
            ?? ($latestScan['eventDescription'] ?? null);

        $tracker->update([
            'status' => TrackerStatus::fromFedex($rawStatus)->value,
            'status_time' => $this->parseScanTimestamp($latestScan),
            'location' => $this->formatLocation($latestScan),
        ]);
    }

    protected function parseScanTimestamp(?array $scan): ?string
    {
        if (! $scan || empty($scan['date'])) {
            return null;
        }

        $timestamp = strtotime($scan['date']);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    protected function formatLocation(?array $scan): ?string
    {
        if (! $scan) {
            return null;
        }

        $address = $scan['scanLocation'] ?? [];

        $parts = array_filter([
            $address['city'] ?? null,
            $address['stateOrProvinceCode'] ?? null,
            $address['countryCode'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
