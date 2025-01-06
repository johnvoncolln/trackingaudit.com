<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UpsTrackingNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // UPS tracking number formats:
        // - 1Z[A-Z0-9]{16} (1Z + 16 alphanumeric)
        // - \d{12} (12 digits)
        // - T\d{10} (T + 10 digits)
        // - \d{9} (9 digits)
        // - \d{26} (26 digits)

        $patterns = [
            '/^1Z[A-Z0-9]{16}$/',
            '/^\d{12}$/',
            '/^T\d{10}$/',
            '/^\d{9}$/',
            '/^\d{26}$/'
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail('The :attribute must be a valid UPS tracking number.');
        }
    }
}
