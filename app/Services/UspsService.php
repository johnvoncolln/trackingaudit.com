<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UspsService
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

        $credentials = base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']);
        $response = Http::withHeaders([
            'Authorization' => "Basic {$credentials}",
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post($this->config['token_url'], [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $expiresIn = $data['expires_in'] - 300; // Refresh 5 minutes before expiration
            Cache::put($cacheKey, $data['access_token'], $expiresIn);

            return $data['access_token'];
        }

        throw new \Exception('Failed to fetch USPS access token: ' . $response->body());
    }

    public function fetchTrackingDetails(string $trackingNumber)
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'TrackingAudit/1.0',
        ])->get("{$this->config['track_url']}/{$trackingNumber}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to fetch USPS tracking details: ' . $response->body());
    }
}
