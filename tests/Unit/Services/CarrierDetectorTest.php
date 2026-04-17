<?php

namespace Tests\Unit\Services;

use App\Enums\Carrier;
use App\Services\CarrierDetector;
use PHPUnit\Framework\TestCase;

class CarrierDetectorTest extends TestCase
{
    // UPS hard rules

    public function test_detects_ups_1z_format(): void
    {
        $this->assertSame(Carrier::UPS, CarrierDetector::detect('1Z12345E0205271688'));
        $this->assertSame(Carrier::UPS, CarrierDetector::detect('1ZISDE016691676846'));
    }

    public function test_detects_ups_t_prefix(): void
    {
        $this->assertSame(Carrier::UPS, CarrierDetector::detect('T1234567890'));
    }

    public function test_detects_ups_9_digits(): void
    {
        $this->assertSame(Carrier::UPS, CarrierDetector::detect('123456789'));
    }

    // FedEx hard rules

    public function test_detects_fedex_96_prefix(): void
    {
        $this->assertSame(Carrier::FEDEX, CarrierDetector::detect('9612345678901234567890'));
    }

    public function test_detects_fedex_7_prefix(): void
    {
        $this->assertSame(Carrier::FEDEX, CarrierDetector::detect('71234567890'));
    }

    public function test_detects_fedex_12_digits(): void
    {
        $this->assertSame(Carrier::FEDEX, CarrierDetector::detect('123456789012'));
    }

    public function test_detects_fedex_14_digits(): void
    {
        $this->assertSame(Carrier::FEDEX, CarrierDetector::detect('12345678901234'));
    }

    public function test_detects_fedex_15_digits(): void
    {
        $this->assertSame(Carrier::FEDEX, CarrierDetector::detect('123456789012345'));
    }

    public function test_detects_fedex_34_digits(): void
    {
        $this->assertSame(Carrier::FEDEX, CarrierDetector::detect('1234567890123456789012345678901234'));
    }

    // USPS known patterns

    public function test_detects_usps_91_through_95_prefix(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('9112345678901234567890'));
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('9212345678901234567890'));
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('9312345678901234567890'));
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('9412345678901234567890'));
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('9512345678901234567890'));
    }

    public function test_detects_usps_ec_international(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('EC123456789US'));
    }

    public function test_detects_usps_s10_international(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('RR123456789US'));
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('CP123456789US'));
    }

    public function test_detects_usps_20_digits(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('12345678901234567890'));
    }

    public function test_detects_usps_22_digits_non_96_prefix(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('1012345678901234567890'));
    }

    public function test_detects_usps_26_digits(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('12345678901234567890123456'));
    }

    public function test_detects_usps_30_digits(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('123456789012345678901234567890'));
    }

    // Default fallback

    public function test_defaults_to_usps_for_unknown_formats(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('UNKNOWN12345'));
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('AB12345678CD'));
    }

    // Case sensitivity

    public function test_handles_lowercase_1z_tracking(): void
    {
        $this->assertSame(Carrier::UPS, CarrierDetector::detect('1z12345e0205271688'));
    }

    public function test_handles_lowercase_ec_tracking(): void
    {
        $this->assertSame(Carrier::USPS, CarrierDetector::detect('ec123456789us'));
    }

    public function test_trims_whitespace(): void
    {
        $this->assertSame(Carrier::UPS, CarrierDetector::detect(' 1Z12345E0205271688 '));
    }
}
