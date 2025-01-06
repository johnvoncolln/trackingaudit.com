<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FedexTrackingNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // FedEx tracking number formats:
        // - \d{12} (12 digits)
        // - \d{14} (14 digits)
        // - \d{15} (15 digits)
        // - \d{20} (20 digits)
        // - \d{22} (22 digits)
        // - \d{34} (34 digits)
        // - 96\d{20} (96 + 20 digits)
        // - 7\d{11} (7 + 11 digits)

        $patterns = [
            '/^\d{12}$/',
            '/^\d{14}$/',
            '/^\d{15}$/',
            '/^\d{20}$/',
            '/^\d{22}$/',
            '/^\d{34}$/',
            '/^96\d{19}$/',  // Changed from 20 to 19 digits after '96' prefix since total should be 21 digits
            '/^7\d{10}$/'  // Changed from 11 to 10 digits after the '7' prefix
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail('The :attribute must be a valid FedEx tracking number.');
        }
    }
}
