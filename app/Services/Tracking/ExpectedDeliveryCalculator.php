<?php

namespace App\Services\Tracking;

use App\Enums\ServiceLevel;
use App\Services\Api\UpsTransitTimeService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ExpectedDeliveryCalculator
{
    public function __construct(protected UpsTransitTimeService $upsTransitTime)
    {
        //
    }

    public function calculate(
        CarbonInterface $shipDate,
        ServiceLevel $service,
        ?string $originZip = null,
        ?string $destinationZip = null,
    ): ?Carbon {
        if ($service === ServiceLevel::UNKNOWN) {
            return null;
        }

        $ship = Carbon::parse($shipDate)->startOfDay();

        if ($service->saturdayDelivery() && $ship->isFriday()) {
            return $ship->copy()->next(CarbonInterface::SATURDAY);
        }

        $days = $service->transitBusinessDays();

        if ($days === null) {
            $days = $this->groundTransitDays($service, $originZip, $destinationZip);
        }

        if ($days === null) {
            return null;
        }

        return $ship->copy()->addWeekdays($days);
    }

    protected function groundTransitDays(ServiceLevel $service, ?string $originZip, ?string $destinationZip): ?int
    {
        if ($service === ServiceLevel::UPS_GROUND) {
            if ($originZip === null || $destinationZip === null) {
                return null;
            }

            return $this->upsTransitTime->lookupBusinessDays($originZip, $destinationZip);
        }

        // FedEx Ground: no first-party TIT available post-facto.
        // Deliberately left as null (not flagged late) for MVP.
        return null;
    }
}
