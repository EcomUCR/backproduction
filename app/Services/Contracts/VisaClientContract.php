<?php

namespace App\Services\Contracts;

use Illuminate\Http\Client\Response;

interface VisaClientContract
{
    /**
     * Envía una solicitud a la API de Visa (o al mock).
     *
     * @param string $endpoint Ruta del endpoint, ej: "/forexrates/v1/foreignexchangerates"
     * @param array $body Datos del cuerpo de la petición (por defecto vacío)
     * @return Response Respuesta HTTP simulada o real
     */
    public function makeRequest(string $endpoint, array $body = []): Response;
}
