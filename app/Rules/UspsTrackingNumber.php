<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UspsTrackingNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // USPS tracking number formats:
        // - \d{20} (20 digits)
        // - \d{26} (26 digits)
        // - \d{30} (30 digits)
        // - 91\d{20} (91 + 20 digits)
        // - 92\d{20} (92 + 20 digits)
        // - 93\d{20} (93 + 20 digits)
        // - 94\d{20} (94 + 20 digits)
        // - 95\d{20} (95 + 20 digits)
        // - \d{2}\d{9}[A-Z]{2}\d{8} (2 digits + 9 digits + 2 letters + 8 digits)
        // - EC\d{9}US (EC + 9 digits + US)
        // - [A-Z]{2}\d{9}[A-Z]{2} (2 letters + 9 digits + 2 letters)

        $patterns = [
            '/^\d{20}$/',                     // 20 digits
            '/^\d{26}$/',                     // 26 digits
            '/^\d{30}$/',                     // 30 digits
            '/^9[1-5]\d{20}$/',              // 91-95 prefix + 20 digits
            '/^\d{2}\d{9}[A-Z]{2}\d{8}$/',   // 2 digits + 9 digits + 2 letters + 8 digits
            '/^EC\d{9}US$/',                  // EC + 9 digits + US
            '/^[A-Z]{2}\d{9}[A-Z]{2}$/'      // 2 letters + 9 digits + 2 letters
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail('The :attribute must be a valid USPS tracking number.');
        }
    }
}
