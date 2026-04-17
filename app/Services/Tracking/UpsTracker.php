<?php

namespace App\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\Tracker;
use App\Models\TrackerData;
use App\Models\User;
use App\Services\Api\UpsApiService;
use Exception;

class UpsTracker implements CarrierTracker
{
    protected $upsService;

    public function __construct(UpsApiService $upsService)
    {
        $this->upsService = $upsService;
    }

    public function track(User $user, array $data): Tracker
    {

        // Fetch tracking details from UPS
        $trackingInfo = $this->upsService->fetchTrackingDetails($data['tracking_number']);

        // Update tracker with latest info
        if (isset($trackingInfo['trackResponse']['shipment'][0]['package'][0])) {
            $package = $trackingInfo['trackResponse']['shipment'][0]['package'][0];
            $latestActivity = $package['activity'][0] ?? null;

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

            if ($latestActivity) {
                $status = TrackerStatus::fromUps($latestActivity['status']['description'] ?? null);
                $statusTime = isset($latestActivity['date'], $latestActivity['time'])
                    ? date('Y-m-d H:i:s', strtotime($latestActivity['date'].' '.$latestActivity['time']))
                    : null;

                $tracker->update([
                    'status' => $status->value,
                    'status_time' => $statusTime,
                    'location' => isset($latestActivity['location'])
                        ? implode(', ', array_filter([
                            $latestActivity['location']['address']['city'] ?? null,
                            $latestActivity['location']['address']['stateProvince'] ?? null,
                            $latestActivity['location']['address']['countryCode'] ?? null,
                        ]))
                        : null,
                    'delivery_date' => $this->parseDeliveryDate($package),
                    'delivered_date' => $status === TrackerStatus::DELIVERED ? $statusTime : $tracker->delivered_date,
                ]);
            }
        } else {
            throw new Exception('Error Processing Request', 1);
        }

        TrackerData::updateOrCreate(
            ['trackers_id' => $tracker->id],
            ['data' => $trackingInfo]
        );

        return $tracker;
    }

    public function update(Tracker $tracker): Tracker
    {
        $trackingInfo = $this->upsService->fetchTrackingDetails($tracker->tracking_number);

        // Update tracker with latest info
        if (isset($trackingInfo['trackResponse']['shipment'][0]['package'][0])) {
            $package = $trackingInfo['trackResponse']['shipment'][0]['package'][0];
            $latestActivity = $package['activity'][0] ?? null;

            if ($latestActivity) {
                $status = TrackerStatus::fromUps($latestActivity['status']['description'] ?? null);
                $statusTime = isset($latestActivity['date'], $latestActivity['time'])
                    ? date('Y-m-d H:i:s', strtotime($latestActivity['date'].' '.$latestActivity['time']))
                    : null;

                $tracker->update([
                    'status' => $status->value,
                    'status_time' => $statusTime,
                    'location' => isset($latestActivity['location'])
                        ? implode(', ', array_filter([
                            $latestActivity['location']['address']['city'] ?? null,
                            $latestActivity['location']['address']['stateProvince'] ?? null,
                            $latestActivity['location']['address']['countryCode'] ?? null,
                        ]))
                        : null,
                    'delivery_date' => $this->parseDeliveryDate($package),
                    'delivered_date' => $status === TrackerStatus::DELIVERED ? $statusTime : $tracker->delivered_date,
                ]);
            }
        }

        // Update tracker data
        TrackerData::updateOrCreate(
            ['trackers_id' => $tracker->id],
            ['data' => $trackingInfo]
        );

        return $tracker->refresh();
    }

    private function parseDeliveryDate(array $package): ?string
    {
        $date = $package['deliveryDate']['date'] ?? null;

        if (! $date) {
            return null;
        }

        $timestamp = strtotime($date);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
