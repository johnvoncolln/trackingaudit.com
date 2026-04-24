<?php

namespace App\Services\Api;

use EasyPost\EasyPostClient as SdkClient;

class EasyPostClient
{
    public function __construct(protected SdkClient $client)
    {
        //
    }

    public static function fromConfig(): self
    {
        return new self(new SdkClient((string) config('tracking.easypost.api_key')));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByTrackingCode(string $trackingCode): ?array
    {
        $response = $this->client->tracker->all([
            'tracking_code' => $trackingCode,
        ]);

        $trackers = $this->normalizeList($response);

        return $trackers[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function createTracker(string $trackingCode, string $carrier): array
    {
        $tracker = $this->client->tracker->create([
            'tracking_code' => $trackingCode,
            'carrier' => $carrier,
        ]);

        return $this->normalize($tracker);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveTracker(string $id): array
    {
        $tracker = $this->client->tracker->retrieve($id);

        return $this->normalize($tracker);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeList(mixed $response): array
    {
        if (is_object($response) && isset($response->trackers)) {
            return array_map(fn ($t) => $this->normalize($t), $response->trackers);
        }

        if (is_array($response) && isset($response['trackers'])) {
            return array_map(fn ($t) => $this->normalize($t), $response['trackers']);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalize(mixed $tracker): array
    {
        if (is_array($tracker)) {
            return $tracker;
        }

        return json_decode(json_encode($tracker), true) ?? [];
    }
}
