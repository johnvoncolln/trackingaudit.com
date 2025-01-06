<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UpsService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.ups');
    }

    public function getAccessToken()
    {
        $cacheKey = 'ups_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $credentials = base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']);
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Authorization' => "Basic {$credentials}",
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post($this->config['token_url'], [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $expiresIn = $data['expires_in'] - 300; // Refresh 5 minutes before expiration
            Cache::put($cacheKey, $data['access_token'], $expiresIn);

            return $data['access_token'];
        }

        throw new \Exception('Failed to fetch UPS access token: ' . $response->body());
    }

    public function fetchTrackingDetails(string $trackingNumber)
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'transId' => uniqid(),
            'transactionSrc' => 'tracking_audit',
        ])->get("{$this->config['track_url']}/{$trackingNumber}", [
            'locale' => 'en_US',
            'returnSignature' => 'false',
            'returnMilestones' => 'false',
            'returnPOD' => 'false',
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to fetch tracking details: ' . $response->body());
    }
}
