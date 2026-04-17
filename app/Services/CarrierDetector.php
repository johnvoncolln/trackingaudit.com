<?php

namespace App\Services;

use App\Enums\Carrier;

class CarrierDetector
{
    /**
     * Detect the carrier from a tracking number format.
     *
     * UPS and FedEx have distinctive patterns checked first.
     * Known USPS patterns are checked next.
     * Anything unmatched defaults to USPS.
     */
    public static function detect(string $trackingNumber): Carrier
    {
        $trackingNumber = strtoupper(trim($trackingNumber));

        // UPS hard rules
        if (preg_match('/^1Z[A-Z0-9]{16}$/', $trackingNumber)) {
            return Carrier::UPS;
        }
        if (preg_match('/^T\d{10}$/', $trackingNumber)) {
            return Carrier::UPS;
        }
        if (preg_match('/^\d{9}$/', $trackingNumber)) {
            return Carrier::UPS;
        }

        // FedEx hard rules
        if (preg_match('/^96\d{20}$/', $trackingNumber)) {
            return Carrier::FEDEX;
        }
        if (preg_match('/^7\d{10}$/', $trackingNumber)) {
            return Carrier::FEDEX;
        }
        if (preg_match('/^\d{12}$/', $trackingNumber)) {
            return Carrier::FEDEX;
        }
        if (preg_match('/^\d{14}$/', $trackingNumber)) {
            return Carrier::FEDEX;
        }
        if (preg_match('/^\d{15}$/', $trackingNumber)) {
            return Carrier::FEDEX;
        }
        if (preg_match('/^\d{34}$/', $trackingNumber)) {
            return Carrier::FEDEX;
        }

        // USPS — everything else defaults here
        return Carrier::USPS;
    }
}
