<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FedexApiService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('services.fedex');
    }

    public function getAccessToken(): string
    {
        $cacheKey = 'fedex_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::asForm()->post($this->config['token_url'], [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['api_key'],
            'client_secret' => $this->config['secret_key'],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $expiresIn = ($data['expires_in'] ?? 3600) - 300;
            Cache::put($cacheKey, $data['access_token'], $expiresIn);

            return $data['access_token'];
        }

        throw new \Exception('Failed to fetch FedEx access token: '.$response->body());
    }

    public function fetchTrackingDetails(string $trackingNumber): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-locale' => 'en_US',
            'Content-Type' => 'application/json',
        ])->post($this->config['track_url'], [
            'includeDetailedScans' => true,
            'trackingInfo' => [
                [
                    'trackingNumberInfo' => [
                        'trackingNumber' => $trackingNumber,
                    ],
                ],
            ],
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to fetch FedEx tracking details: '.$response->body());
    }
}
