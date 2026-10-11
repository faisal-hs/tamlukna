<?php

return [

    'locales' => [
        // Order is irrelevant; the fallback is config('app.locale').
        'supported' => ['ar', 'en'],
    ],

    'pagination' => [
        'default_per_page' => (int) env('API_DEFAULT_PER_PAGE', 15),
        'max_per_page' => (int) env('API_MAX_PER_PAGE', 50),
    ],

    'rate_limits' => [
        'api_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    ],

];
