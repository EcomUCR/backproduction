<?php

namespace App\Services;

use App\Services\Contracts\VisaClientContract;
use Illuminate\Http\Client\Response as HttpClientResponse;
use GuzzleHttp\Psr7\Response as Psr7Response;

class MockVisaClient implements VisaClientContract
{
    public function makeRequest(string $endpoint, array $body = []): HttpClientResponse
    {
        // Ejemplo de mapeo simple por endpoint
        if ($endpoint === '/forexrates/v1/foreignexchangerates') {
            $payload = [
                'data' => [
                    'destinationCurrencyCode' => $body['destinationCurrencyCode'] ?? 'USD',
                    'sourceCurrencyCode'      => $body['sourceCurrencyCode'] ?? 'CRC',
                    'rate'                    => '0.0017',
                    'timestamp'               => now()->toIso8601String(),
                    'mock'                    => true,
                ],
            ];

            $psr = new Psr7Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            );

            return new HttpClientResponse($psr);
        }

        // Respuesta por defecto si el endpoint no está mockeado
        $psr = new Psr7Response(
            404,
            ['Content-Type' => 'application/json'],
            json_encode([
                'error'   => true,
                'message' => 'Mock not implemented for endpoint: ' . $endpoint,
            ])
        );

        return new HttpClientResponse($psr);
    }
}
