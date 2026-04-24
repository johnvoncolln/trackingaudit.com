<?php

namespace App\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\Tracker;
use App\Models\TrackerData;
use App\Models\User;
use App\Services\Api\EasyPostClient;
use Exception;

class EasyPostTracker implements CarrierTracker
{
    public function __construct(protected EasyPostClient $client)
    {
        //
    }

    public function track(User $user, array $data): Tracker
    {
        $trackingNumber = $data['tracking_number'];
        $carrier = $data['carrier'];

        $payload = $this->client->findByTrackingCode($trackingNumber)
            ?? $this->client->createTracker($trackingNumber, $carrier);

        $tracker = Tracker::firstOrCreate(
            ['tracking_number' => $trackingNumber],
            [
                'user_id' => $user->id,
                'carrier' => $carrier,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_name' => $data['reference_name'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_email' => $data['recipient_email'] ?? null,
            ]
        );

        $this->applyPayload($tracker, $payload);

        return $tracker->refresh();
    }

    public function update(Tracker $tracker): Tracker
    {
        $payload = $tracker->easypost_id
            ? $this->client->retrieveTracker($tracker->easypost_id)
            : $this->client->findByTrackingCode($tracker->tracking_number);

        if (! $payload) {
            throw new Exception("EasyPost tracker not found for {$tracker->tracking_number}");
        }

        $this->applyPayload($tracker, $payload);

        return $tracker->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyPayload(Tracker $tracker, array $payload): void
    {
        $status = TrackerStatus::fromEasyPost($payload['status'] ?? null);
        $latestDetail = $this->latestDetail($payload);
        $statusTime = $this->parseDatetime($latestDetail['datetime'] ?? null);

        $tracker->update([
            'easypost_id' => $payload['id'] ?? $tracker->easypost_id,
            'status' => $status->value,
            'status_time' => $statusTime,
            'location' => $this->formatLocation($latestDetail['tracking_location'] ?? null),
            'delivery_date' => $this->parseDatetime($payload['est_delivery_date'] ?? null),
            'delivered_date' => $status === TrackerStatus::DELIVERED
                ? ($this->parseDatetime($this->deliveredAt($payload)) ?? $tracker->delivered_date)
                : $tracker->delivered_date,
        ]);

        TrackerData::updateOrCreate(
            ['trackers_id' => $tracker->id],
            ['data' => $payload]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function latestDetail(array $payload): ?array
    {
        $details = $payload['tracking_details'] ?? [];

        if (! is_array($details) || $details === []) {
            return null;
        }

        return end($details) ?: null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function deliveredAt(array $payload): ?string
    {
        foreach (array_reverse($payload['tracking_details'] ?? []) as $detail) {
            if (($detail['status'] ?? null) === 'delivered') {
                return $detail['datetime'] ?? null;
            }
        }

        return $this->latestDetail($payload)['datetime'] ?? null;
    }

    /**
     * @param  array<string, mixed>|null  $location
     */
    protected function formatLocation(?array $location): ?string
    {
        if (! $location) {
            return null;
        }

        $parts = array_filter([
            $location['city'] ?? null,
            $location['state'] ?? null,
            $location['country'] ?? null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    protected function parseDatetime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
