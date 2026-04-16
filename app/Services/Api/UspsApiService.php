<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UspsApiService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.usps');
    }

    public function getAccessToken()
    {
        $cacheKey = 'usps_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::asForm()->post($this->config['token_url'], [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['consumer_key'],
            'client_secret' => $this->config['consumer_secret'],
            'scope' => 'tracking',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $expiresIn = $data['expires_in'] - 300; // Refresh 5 minutes before expiration
            Cache::put($cacheKey, $data['access_token'], $expiresIn);

            return $data['access_token'];
        }

        throw new \Exception('Failed to fetch USPS access token: '.$response->body());
    }

    public function fetchTrackingDetails(string $trackingNumber)
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get("{$this->config['track_url']}/{$trackingNumber}", [
            'expand' => 'DETAIL',
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to fetch USPS tracking details: '.$response->body());
    }
}
