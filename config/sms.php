<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMPP SMS API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for the SMPP SMS service.
    | These credentials are used to authenticate with the SMS gateway.
    |
    */

    'api_url' => env('SMPP_API_URL', 'http://10.172.172.2/api'),
    'email' => env('SMPP_API_EMAIL'),
    'password' => env('SMPP_API_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Endpoints (اختياري - إن لم تُحدَّد تُشتق من api_url)
    |--------------------------------------------------------------------------
    | مثال: api_url = http://10.172.172.2/api
    |       login_url تلقائياً = http://10.172.172.2/api/login
    |       send_url  تلقائياً = http://10.172.172.2/api/send
    */

    'login_url' => env('SMPP_LOGIN_URL'),
    'send_url' => env('SMPP_SEND_URL'),

    /*
    |--------------------------------------------------------------------------
    | Connection & Retry
    |--------------------------------------------------------------------------
    */

    'timeout' => env('SMPP_TIMEOUT', 15),
    'connect_timeout' => env('SMPP_CONNECT_TIMEOUT', 5),
    'verify_ssl' => env('SMPP_VERIFY_SSL', false),

    'token_ttl' => env('SMPP_TOKEN_TTL', 3300),
    'retries' => env('SMPP_RETRIES', 2),
    'retry_delay_ms' => env('SMPP_RETRY_DELAY_MS', 700),

];
