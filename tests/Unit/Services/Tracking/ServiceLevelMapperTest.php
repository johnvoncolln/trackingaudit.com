<?php

namespace Tests\Unit\Services\Tracking;

use App\Enums\ServiceLevel;
use App\Services\Tracking\ServiceLevelMapper;
use Tests\TestCase;

class ServiceLevelMapperTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, mixed>, 1: ServiceLevel}>
     */
    public static function easypostProvider(): array
    {
        return [
            'UPS Next Day Air' => [
                ['carrier' => 'UPS', 'carrier_detail' => ['service' => 'Next Day Air']],
                ServiceLevel::UPS_NEXT_DAY_AIR,
            ],
            'UPS Next Day Air Saver' => [
                ['carrier' => 'UPS', 'carrier_detail' => ['service' => 'Next Day Air Saver']],
                ServiceLevel::UPS_NEXT_DAY_AIR_SAVER,
            ],
            'UPS Next Day Air Saturday' => [
                ['carrier' => 'UPS', 'carrier_detail' => ['service' => 'Next Day Air Saturday Delivery']],
                ServiceLevel::UPS_NEXT_DAY_AIR_SATURDAY,
            ],
            'UPS 2nd Day Air' => [
                ['carrier' => 'UPS', 'carrier_detail' => ['service' => '2nd Day Air']],
                ServiceLevel::UPS_2ND_DAY_AIR,
            ],
            'UPS 3 Day Select' => [
                ['carrier' => 'UPS', 'carrier_detail' => ['service' => '3 Day Select']],
                ServiceLevel::UPS_3_DAY_SELECT,
            ],
            'UPS Ground' => [
                ['carrier' => 'UPS', 'carrier_detail' => ['service' => 'Ground']],
                ServiceLevel::UPS_GROUND,
            ],
            'FedEx First Overnight' => [
                ['carrier' => 'FedEx', 'carrier_detail' => ['service' => 'FedEx First Overnight']],
                ServiceLevel::FEDEX_FIRST_OVERNIGHT,
            ],
            'FedEx Priority Overnight' => [
                ['carrier' => 'FedEx', 'carrier_detail' => ['service' => 'Priority Overnight']],
                ServiceLevel::FEDEX_PRIORITY_OVERNIGHT,
            ],
            'FedEx 2Day' => [
                ['carrier' => 'FedEx', 'carrier_detail' => ['service' => 'FedEx 2Day']],
                ServiceLevel::FEDEX_2DAY,
            ],
            'FedEx Express Saver' => [
                ['carrier' => 'FedEx', 'carrier_detail' => ['service' => 'FedEx Express Saver']],
                ServiceLevel::FEDEX_EXPRESS_SAVER,
            ],
            'FedEx Ground' => [
                ['carrier' => 'FedEx', 'carrier_detail' => ['service' => 'FedEx Ground']],
                ServiceLevel::FEDEX_GROUND,
            ],
            'FedEx Home Delivery (Ground)' => [
                ['carrier' => 'FedEx', 'carrier_detail' => ['service' => 'FedEx Home Delivery']],
                ServiceLevel::FEDEX_GROUND,
            ],
            'USPS' => [
                ['carrier' => 'USPS', 'carrier_detail' => ['service' => 'First-Class Package Service']],
                ServiceLevel::UNKNOWN,
            ],
            'missing service' => [
                ['carrier' => 'UPS', 'carrier_detail' => []],
                ServiceLevel::UNKNOWN,
            ],
            'unknown UPS service' => [
                ['carrier' => 'UPS', 'carrier_detail' => ['service' => 'SurePost']],
                ServiceLevel::UNKNOWN,
            ],
        ];
    }

    /**
     * @dataProvider easypostProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_for_easypost_maps_service_strings(array $payload, ServiceLevel $expected): void
    {
        $this->assertSame($expected, ServiceLevelMapper::forEasyPost($payload));
    }

    public function test_for_ups_reads_raw_payload(): void
    {
        $payload = [
            'trackResponse' => [
                'shipment' => [
                    [
                        'service' => ['description' => 'UPS Next Day Air'],
                        'package' => [[]],
                    ],
                ],
            ],
        ];

        $this->assertSame(ServiceLevel::UPS_NEXT_DAY_AIR, ServiceLevelMapper::forUps($payload));
    }

    public function test_for_ups_returns_unknown_when_service_missing(): void
    {
        $this->assertSame(ServiceLevel::UNKNOWN, ServiceLevelMapper::forUps([]));
    }

    public function test_for_fedex_reads_raw_payload(): void
    {
        $payload = [
            'output' => [
                'completeTrackResults' => [
                    [
                        'trackResults' => [
                            [
                                'serviceDetail' => ['description' => 'FedEx Priority Overnight'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame(ServiceLevel::FEDEX_PRIORITY_OVERNIGHT, ServiceLevelMapper::forFedex($payload));
    }

    public function test_for_fedex_returns_unknown_when_service_missing(): void
    {
        $this->assertSame(ServiceLevel::UNKNOWN, ServiceLevelMapper::forFedex([]));
    }
}
