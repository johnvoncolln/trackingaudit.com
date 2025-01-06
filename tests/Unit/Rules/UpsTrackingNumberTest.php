<?php

namespace Tests\Unit\Rules;

use App\Rules\UpsTrackingNumber;
use PHPUnit\Framework\TestCase;

class UpsTrackingNumberTest extends TestCase
{
    private UpsTrackingNumber $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new UpsTrackingNumber();
    }

    /**
     * @dataProvider validTrackingNumbersProvider
     */
    public function test_valid_tracking_numbers_pass_validation($trackingNumber)
    {
        $fails = false;
        $this->rule->validate('tracking_number', $trackingNumber, function() use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails, "Tracking number {$trackingNumber} should be valid");
    }

    /**
     * @dataProvider invalidTrackingNumbersProvider
     */
    public function test_invalid_tracking_numbers_fail_validation($trackingNumber)
    {
        $fails = false;
        $this->rule->validate('tracking_number', $trackingNumber, function() use (&$fails) {
            $fails = true;
        });

        $this->assertTrue($fails, "Tracking number {$trackingNumber} should be invalid");
    }

    public static function validTrackingNumbersProvider()
    {
        return [
            'standard_1Z_format' => ['1Z999AA1234567890'],
            'twelve_digit_format' => ['123456789012'],
            'nine_digit_format' => ['123456789'],
            'T_plus_ten_digits' => ['T1234567890'],
            'twenty_six_digits' => ['12345678901234567890123456']
        ];
    }

    public static function invalidTrackingNumbersProvider()
    {
        return [
            'empty_string' => [''],
            'too_short' => ['12345678'],
            'too_long' => ['1Z999AA12345678901'],
            'invalid_1Z_format' => ['1Y999AA1234567890'],
            'invalid_T_format' => ['A1234567890'],
            'special_characters' => ['1Z999AA123456@#$'],
            'with_spaces' => ['1Z999 AA1234567890']
        ];
    }
}
