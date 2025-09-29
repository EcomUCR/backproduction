<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí defines la configuración de CORS.
    | Ajustado para permitir que el frontend en Vite (5173) acceda al backend.
    |
    */

    // Solo aplicamos CORS en las rutas de la API
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173', // tu entorno local de React/Vite
       /*>>>>>>>>>>>>>>>> */ 'https://TUDOMINIO.FRONTEND.com', // tu futuro dominio de producción (opcional) //RECORDAR CAMBIAR<<<<<<<<<<<<<<<<<<<<<<<<
        // Puedes agregar otros orígenes aquí
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
