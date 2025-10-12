<?php

namespace App\Services;

use App\Services\Contracts\VisaClientContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class VisaClient implements VisaClientContract
{
    public function makeRequest(string $endpoint, array $body = []): Response
    {
        $cfg = config('services.visa');

        return Http::withOptions([
            'cert'    => $cfg['cert_path'],
            'ssl_key' => $cfg['key_path'],
            'verify'  => $cfg['ca_path'],
        ])->withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($cfg['api_key'] . ':'),
        ])->post(rtrim($cfg['base_url'], '/') . $endpoint, $body);
    }
}
