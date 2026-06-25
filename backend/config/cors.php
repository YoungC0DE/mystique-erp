<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Necessário quando o frontend usa URL absoluta (ex.: http://localhost:8000/api).
    | Com o proxy do Vite (VITE_API_URL=/api), o browser não faz cross-origin.
    |
    */

    'paths' => ['api/*', 'oauth/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        'http://localhost:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        env('FRONTEND_URL'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
