<?php

/*
 * CORS is deny-by-default: browsers may only call the API from origins
 * listed explicitly in CORS_ALLOWED_ORIGINS (comma-separated, exact match).
 */

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept', 'Accept-Language', 'Authorization', 'Content-Type',
        'X-Requested-With', 'X-Request-Id', 'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-Request-Id', 'Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Content-Language',
    ],

    'max_age' => 3600,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
