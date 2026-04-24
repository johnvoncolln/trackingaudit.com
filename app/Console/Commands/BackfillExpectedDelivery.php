<?php

namespace App\Console\Commands;

use App\Enums\Carrier;
use App\Enums\ServiceLevel;
use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use App\Services\Tracking\ExpectedDeliveryCalculator;
use App\Services\Tracking\ServiceLevelMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BackfillExpectedDelivery extends Command
{
    protected $signature = 'tracking:backfill-expected-delivery {--chunk=100} {--limit=} {--dry-run} {--refresh}';

    protected $description = 'Populate service_type, ship_date, expected_delivery_date for UPS/FedEx trackers from stored tracker_data payloads. --refresh dispatches UpdateTrackerJob for legacy-format rows to pull the latest EasyPost payload first.';

    public function handle(ExpectedDeliveryCalculator $calculator): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $dryRun = (bool) $this->option('dry-run');
        $refresh = (bool) $this->option('refresh');

        $query = Tracker::query()
            ->whereIn('carrier', [Carrier::UPS->value, Carrier::FEDEX->value])
            ->with('trackerData');

        $total = $limit ?? $query->count();
        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Processing up to {$total} UPS/FedEx trackers...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $refreshed = 0;
        $failed = 0;

        $query->chunkById($chunkSize, function ($trackers) use ($calculator, $dryRun, $refresh, $limit, &$processed, &$updated, &$skipped, &$refreshed, &$failed, $bar) {
            foreach ($trackers as $tracker) {
                if ($limit !== null && $processed >= $limit) {
                    return false;
                }

                $processed++;
                $bar->advance();

                $payload = $tracker->trackerData?->data;

                if (! is_array($payload)) {
                    $skipped++;

                    continue;
                }

                try {
                    if ($refresh && $this->isLegacyPayload($payload)) {
                        if (! $dryRun) {
                            UpdateTrackerJob::dispatch($tracker);
                        }
                        $refreshed++;

                        continue;
                    }

                    [$service, $originZip, $destinationZip, $shipDate, $serviceType] = $this->extract($tracker, $payload);

                    $expected = $calculator->calculate($shipDate, $service, $originZip, $destinationZip);

                    $attributes = [
                        'service_type' => $serviceType,
                        'service_code' => $service === ServiceLevel::UNKNOWN ? null : $service->value,
                        'ship_date' => $shipDate->toDateString(),
                        'expected_delivery_date' => $expected?->toDateString(),
                        'origin_zip' => $originZip,
                        'destination_zip' => $destinationZip,
                    ];

                    if ($dryRun) {
                        $this->newLine();
                        $this->line("  #{$tracker->id} {$tracker->tracking_number} → ".json_encode($attributes));

                        continue;
                    }

                    $tracker->update($attributes);
                    $updated++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('tracking.backfill.row_failed', [
                        'tracker_id' => $tracker->id,
                        'tracking_number' => $tracker->tracking_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return true;
        });

        $bar->finish();
        $this->newLine(2);
        $summary = "Processed: {$processed} | Updated: {$updated} | Skipped (no payload): {$skipped} | Failed: {$failed}";
        if ($refresh) {
            $summary .= " | Queued refresh: {$refreshed}";
        }
        $this->info($summary);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function isLegacyPayload(array $payload): bool
    {
        return ! isset($payload['carrier_detail']) && ! isset($payload['tracking_details']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: ServiceLevel, 1: ?string, 2: ?string, 3: \Illuminate\Support\Carbon, 4: ?string}
     */
    protected function extract(Tracker $tracker, array $payload): array
    {
        if (isset($payload['carrier_detail']) || isset($payload['tracking_details'])) {
            $service = ServiceLevelMapper::forEasyPost($payload);
            $originZip = $this->extractZip($payload['carrier_detail']['origin_location'] ?? null);
            $destinationZip = $this->extractZip($payload['carrier_detail']['destination_location'] ?? null);
            $shipDateString = $this->earliestPreTransit($payload);
            $serviceType = $payload['carrier_detail']['service'] ?? null;
        } elseif ($tracker->carrier === Carrier::UPS->value) {
            $service = ServiceLevelMapper::forUps($payload);
            $originZip = null;
            $destinationZip = null;
            $shipDateString = null;
            $serviceType = $payload['trackResponse']['shipment'][0]['package'][0]['service']['description']
                ?? $payload['trackResponse']['shipment'][0]['service']['description']
                ?? null;
        } else {
            $service = ServiceLevelMapper::forFedex($payload);
            $originZip = null;
            $destinationZip = null;
            $shipDateString = null;
            $serviceType = $payload['output']['completeTrackResults'][0]['trackResults'][0]['serviceDetail']['description'] ?? null;
        }

        $shipDate = $shipDateString
            ? Carbon::parse($shipDateString)
            : Carbon::parse($tracker->created_at);

        return [$service, $originZip, $destinationZip, $shipDate, $serviceType];
    }

    protected function extractZip(?string $location): ?string
    {
        if (! is_string($location) || $location === '') {
            return null;
        }

        if (preg_match('/\b(\d{5})(?:-\d{4})?\b\s*$/', trim($location), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function earliestPreTransit(array $payload): ?string
    {
        foreach ($payload['tracking_details'] ?? [] as $detail) {
            if (($detail['status'] ?? null) === 'pre_transit') {
                return $detail['datetime'] ?? null;
            }
        }

        return null;
    }
}
