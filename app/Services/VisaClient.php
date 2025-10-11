<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VisaClient
{
    public static function makeRequest(string $endpoint, array $body = [])
    {
        $baseUrl = env('VISA_BASE_URL');
        $certPath = base_path(env('VISA_CERT_PATH'));
        $keyPath = base_path(env('VISA_KEY_PATH'));
        $caPath = base_path(env('VISA_CA_PATH'));
        $apiKey = env('VISA_API_KEY');

        return Http::withOptions([
            'cert' => $certPath,
            'ssl_key' => $keyPath,
            'verify' => $caPath,
        ])->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
        ])->post("{$baseUrl}{$endpoint}", $body);
    }
}
