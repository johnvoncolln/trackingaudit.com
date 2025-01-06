<?php

namespace Tests\Unit\Rules;

use App\Rules\UspsTrackingNumber;
use PHPUnit\Framework\TestCase;

class UspsTrackingNumberTest extends TestCase
{
    private UspsTrackingNumber $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new UspsTrackingNumber();
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
            'twenty_digits' => ['12345678901234567890'],
            'twenty_six_digits' => ['12345678901234567890123456'],
            'thirty_digits' => ['123456789012345678901234567890'],
            '91_prefix' => ['91123456789012345678901'],
            '92_prefix' => ['92123456789012345678901'],
            '93_prefix' => ['93123456789012345678901'],
            '94_prefix' => ['94123456789012345678901'],
            '95_prefix' => ['95123456789012345678901'],
            'mixed_format' => ['12123456789AB12345678'],
            'international_ec' => ['EC123456789US'],
            'international_letters' => ['AA123456789BB']
        ];
    }

    public static function invalidTrackingNumbersProvider()
    {
        return [
            'empty_string' => [''],
            'too_short' => ['123456789'],
            'invalid_91_prefix' => ['91123456789'],
            'invalid_ec_format' => ['EC123456789'],
            'special_characters' => ['91123456789@#$%^&*()'],
            'with_spaces' => ['9112345 6789012345678901'],
            'letters_in_wrong_place' => ['91ABC123456789012345678'],
            'wrong_international_prefix' => ['XX123456789US']
        ];
    }
}
