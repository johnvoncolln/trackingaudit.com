<?php

namespace Tests\Unit\Services\Api;

use App\Services\Api\EasyPostClient;
use EasyPost\EasyPostClient as SdkClient;
use EasyPost\Service\TrackerService;
use Tests\TestCase;

class EasyPostClientTest extends TestCase
{
    public function test_normalizes_sdk_objects_using_to_array(): void
    {
        $sdkTracker = new class
        {
            public function __toArray(bool $recursive = false): array
            {
                return [
                    'id' => 'trk_fromobject',
                    'tracking_code' => 'EZ1000000001',
                    'status' => 'in_transit',
                    'est_delivery_date' => '2026-04-30T00:00:00Z',
                    'tracking_details' => [],
                ];
            }
        };

        $collection = new class($sdkTracker)
        {
            /** @var array<int, object> */
            public array $trackers;

            public function __construct(object $tracker)
            {
                $this->trackers = [$tracker];
            }
        };

        $trackerService = $this->createMock(TrackerService::class);
        $trackerService->method('all')->willReturn($collection);

        $sdk = $this->createMock(SdkClient::class);
        $sdk->tracker = $trackerService;

        $client = new EasyPostClient($sdk);

        $result = $client->findByTrackingCode('EZ1000000001');

        $this->assertIsArray($result);
        $this->assertSame('trk_fromobject', $result['id']);
        $this->assertSame('in_transit', $result['status']);
        $this->assertSame('EZ1000000001', $result['tracking_code']);
    }

    public function test_returns_null_when_no_trackers_match(): void
    {
        $emptyCollection = new class
        {
            public array $trackers = [];
        };

        $trackerService = $this->createMock(TrackerService::class);
        $trackerService->method('all')->willReturn($emptyCollection);

        $sdk = $this->createMock(SdkClient::class);
        $sdk->tracker = $trackerService;

        $client = new EasyPostClient($sdk);

        $this->assertNull($client->findByTrackingCode('EZ0000000000'));
    }
}
