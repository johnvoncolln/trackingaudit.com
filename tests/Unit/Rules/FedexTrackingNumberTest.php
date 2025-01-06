<?php

namespace Tests\Unit\Rules;

use App\Rules\FedexTrackingNumber;
use PHPUnit\Framework\TestCase;

class FedexTrackingNumberTest extends TestCase
{
    private FedexTrackingNumber $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new FedexTrackingNumber();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validTrackingNumbersProvider')]
    public function test_valid_tracking_numbers_pass_validation(string $trackingNumber)
    {
        $fails = false;
        $this->rule->validate('tracking_number', $trackingNumber, function() use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails, "Tracking number {$trackingNumber} should be valid");
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTrackingNumbersProvider')]
    public function test_invalid_tracking_numbers_fail_validation(string $trackingNumber)
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
            'twelve_digits' => ['123456789012'],
            'fourteen_digits' => ['12345678901234'],
            'fifteen_digits' => ['123456789012345'],
            'twenty_digits' => ['12345678901234567890'],
            'twenty_two_digits' => ['1234567890123456789012'],
            'thirty_four_digits' => ['1234567890123456789012345678901234'],
            '96_prefix' => ['96123456789012345678901'],
            '7_prefix' => ['71234567890']
        ];
    }

    public static function invalidTrackingNumbersProvider()
    {
        return [
            'empty_string' => [''],
            'too_short' => ['12345'],
            'too_long' => ['123456789012345678901234567890123456'],
            'invalid_96_prefix' => ['96123'],
            'invalid_7_prefix' => ['7123'],
            'special_characters' => ['123456789@#$%'],
            'with_spaces' => ['1234 56789012'],
            'with_letters' => ['123ABC456789']
        ];
    }
}
