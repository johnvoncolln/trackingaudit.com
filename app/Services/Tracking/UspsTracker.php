<?php

namespace App\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\Tracker;
use App\Models\TrackerData;
use App\Models\User;
use App\Services\Api\UspsApiService;
use Exception;

class UspsTracker implements CarrierTracker
{
    public function __construct(protected UspsApiService $uspsService) {}

    public function track(User $user, array $data): Tracker
    {
        $trackingInfo = $this->uspsService->fetchTrackingDetails($data['tracking_number']);

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
        $trackingInfo = $this->uspsService->fetchTrackingDetails($tracker->tracking_number);

        $this->applyResponseToTracker($tracker, $trackingInfo);

        TrackerData::updateOrCreate(
            ['trackers_id' => $tracker->id],
            ['data' => $trackingInfo]
        );

        return $tracker->refresh();
    }

    protected function applyResponseToTracker(Tracker $tracker, array $trackingInfo): void
    {
        if (! isset($trackingInfo['trackingNumber'])) {
            throw new Exception('Unexpected USPS tracking response.');
        }

        $latestEvent = $trackingInfo['trackingEvents'][0] ?? null;

        if (! $latestEvent) {
            return;
        }

        $rawStatus = $latestEvent['eventType'] ?? ($trackingInfo['statusSummary'] ?? null);
        $status = TrackerStatus::fromUsps($rawStatus);
        $statusTime = $this->parseEventTimestamp($latestEvent);

        $tracker->update([
            'status' => $status->value,
            'status_time' => $statusTime,
            'location' => $this->formatLocation($latestEvent),
            'delivery_date' => $this->parseExpectedDelivery($trackingInfo),
            'delivered_date' => $status === TrackerStatus::DELIVERED ? $statusTime : $tracker->delivered_date,
        ]);
    }

    protected function parseEventTimestamp(array $event): ?string
    {
        $date = $event['eventDate'] ?? null;
        $time = $event['eventTime'] ?? null;

        if (! $date) {
            return null;
        }

        $timestamp = strtotime(trim($date.' '.($time ?? '')));

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    protected function formatLocation(array $event): ?string
    {
        $parts = array_filter([
            $event['eventCity'] ?? null,
            $event['eventState'] ?? null,
            $event['eventCountry'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    private function parseExpectedDelivery(array $trackingInfo): ?string
    {
        $date = $trackingInfo['expectedDeliveryDate'] ?? null;

        if (! $date) {
            return null;
        }

        $timestamp = strtotime($date);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
