<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | FRONTEND_URL must be set in .env to match the Next.js domain.
    | For local development, set FRONTEND_URL=https://www.tripogramclub.com
    | For production, set FRONTEND_URL=https://tripogramclub.com
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'https://tripogramclub.com'),
        'https://www.tripogramclub.com',
        'http://127.0.0.1:3000',
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
