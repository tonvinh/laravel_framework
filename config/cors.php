<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     * Thay thế bằng domain thực tế của frontend trong production.
     * Ví dụ: 'https://app.example.com'
     */
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173,http://localhost:8000')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
