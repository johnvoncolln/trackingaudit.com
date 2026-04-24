<?php

namespace Tests\Unit\Enums;

use App\Enums\TrackerStatus;
use Tests\TestCase;

class TrackerStatusFromEasyPostTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: TrackerStatus}>
     */
    public static function easypostStatusProvider(): array
    {
        return [
            'unknown' => ['unknown', TrackerStatus::UNKNOWN],
            'pre_transit' => ['pre_transit', TrackerStatus::PRE_TRANSIT],
            'in_transit' => ['in_transit', TrackerStatus::IN_TRANSIT],
            'out_for_delivery' => ['out_for_delivery', TrackerStatus::OUT_FOR_DELIVERY],
            'delivered' => ['delivered', TrackerStatus::DELIVERED],
            'available_for_pickup' => ['available_for_pickup', TrackerStatus::AVAILABLE_FOR_PICKUP],
            'return_to_sender' => ['return_to_sender', TrackerStatus::RETURN_TO_SENDER],
            'failure' => ['failure', TrackerStatus::FAILURE],
            'cancelled' => ['cancelled', TrackerStatus::CANCELLED],
            'error' => ['error', TrackerStatus::ERROR],
            'null input' => [null, TrackerStatus::UNKNOWN],
            'empty string' => ['', TrackerStatus::UNKNOWN],
            'garbage' => ['something_else', TrackerStatus::UNKNOWN],
            'uppercase maps' => ['DELIVERED', TrackerStatus::DELIVERED],
        ];
    }

    /**
     * @dataProvider easypostStatusProvider
     */
    public function test_it_maps_easypost_status_strings(?string $raw, TrackerStatus $expected): void
    {
        $this->assertSame($expected, TrackerStatus::fromEasyPost($raw));
    }
}
