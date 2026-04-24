<?php

namespace App\Services\Api;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpsTransitTimeService
{
    /** @var array<string, mixed> */
    protected array $config;

    public function __construct(protected UpsApiService $upsApiService)
    {
        $this->config = config('services.ups');
    }

    public function lookupBusinessDays(string $originZip, string $destinationZip): ?int
    {
        $key = "ups:tit:{$originZip}:{$destinationZip}:ground";

        return Cache::remember($key, now()->addDays(7), function () use ($originZip, $destinationZip) {
            return $this->fetch($originZip, $destinationZip);
        });
    }

    protected function fetch(string $originZip, string $destinationZip): ?int
    {
        $transitUrl = $this->config['transit_url'] ?? null;

        if (! is_string($transitUrl) || $transitUrl === '') {
            Log::warning('ups.tit.missing_transit_url');

            return null;
        }

        try {
            $token = $this->upsApiService->getAccessToken();
        } catch (\Throwable $e) {
            Log::warning('ups.tit.token_failed', ['error' => $e->getMessage()]);

            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'transId' => uniqid('tit_', true),
            'transactionSrc' => 'tracking-app',
        ])->post($transitUrl, [
            'originCountryCode' => 'US',
            'originPostalCode' => $originZip,
            'destinationCountryCode' => 'US',
            'destinationPostalCode' => $destinationZip,
            'weight' => '1',
            'weightUnitOfMeasure' => 'LBS',
            'shipDate' => Carbon::now()->format('Y-m-d'),
            'shipTime' => '1200',
        ]);

        if (! $response->successful()) {
            Log::warning('ups.tit.http_failed', [
                'origin' => $originZip,
                'destination' => $destinationZip,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $services = $response->json('emsResponse.services') ?? [];

        foreach ($services as $service) {
            if (($service['serviceLevel'] ?? null) === 'GND') {
                $days = $service['businessTransitDays'] ?? null;

                return is_numeric($days) ? (int) $days : null;
            }
        }

        Log::info('ups.tit.no_ground_service', [
            'origin' => $originZip,
            'destination' => $destinationZip,
        ]);

        return null;
    }
}
