<?php

return [

    'locales' => [
        // Locales accepted from Accept-Language.
        'supported' => ['ar', 'en'],
        // Stable default. Not config('app.locale'): App::setLocale() overwrites that at runtime.
        'default' => env('APP_LOCALE', 'ar'),
    ],

    'pagination' => [
        'default_per_page' => (int) env('API_DEFAULT_PER_PAGE', 15),
        'max_per_page' => (int) env('API_MAX_PER_PAGE', 50),
    ],

    'rate_limits' => [
        'api_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
        'auth_per_minute' => (int) env('AUTH_RATE_LIMIT_PER_MINUTE', 10),
    ],

    'frontend' => [
        // Page (website or app deep link) that shows the "choose a new password" form.
        // {token} and {email} are replaced (URL-encoded) when the email is sent.
        'password_reset_url' => env(
            'FRONTEND_PASSWORD_RESET_URL',
            'http://localhost:3000/reset-password?token={token}&email={email}'
        ),
    ],

];
