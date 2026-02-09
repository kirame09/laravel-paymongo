<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayMongo Environment
    |--------------------------------------------------------------------------
    |
    | Specifies whether to use sandbox or production environment.
    | Options: 'sandbox', 'production'
    |
    */

    'environment' => env('PAYMONGO_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | PayMongo Keys
    |--------------------------------------------------------------------------
    |
    | The PayMongo public and secret keys give you access to PayMongo's
    | Payment Gateway API.
    |
    */

    'public_key' => env('PAYMONGO_PUBLIC_KEY'),

    'secret_key' => env('PAYMONGO_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | PayMongo Webhook Secret
    |--------------------------------------------------------------------------
    |
    | This is used to verify that webhook requests are actually coming from
    | PayMongo and not a malicious third party.
    |
    */

    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | PayMongo API endpoint.
    |
    */

    'base_url' => 'https://api.paymongo.com/v1',

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for PayMongo transactions (PHP for Philippines).
    |
    */

    'currency' => 'PHP',
];
