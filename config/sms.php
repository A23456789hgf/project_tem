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

    'api_url' => env('SMPP_API_URL', 'http://10.172.172.2:8866/api'),
    'email' => env('SMPP_API_EMAIL'),
    'password' => env('SMPP_API_PASSWORD'),

];
