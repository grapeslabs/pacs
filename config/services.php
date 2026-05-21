<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'dadata' => [
        'token' => env('DADATA_TOKEN'),
        'secret' => env('DADATA_SECRET'),
        'url' => env('DADATA_URL', 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party'),
        'timeout' => env('DADATA_TIMEOUT', 10),
    ],
    'ms' => [
        'enabled' => env('MEDIA_SERVER_ENABLED', false) || env('ANALYTIC_ENABLED', false),
        'url' => env('MEDIA_SERVER_API_URL'),
        'rtsp_url' => env('MEDIA_SERVER_RTSP'),
        'timeout' => env('MEDIA_SERVER_API_TIMEOUT', 5),
    ],
    'va' => [
        'enabled'=> env('ANALYTIC_ENABLED', false),
        'url' => env('ANALYTIC_HOST'),
        'timeout' => env('ANALYTIC_TIMEOUT', 5),
        'timedelay' => env('ANALYTIC_TIMEDELAY', 200)
    ],
    'yacaptcha' => [
        'secret' => env('YACAPTCHA_SECRET'),
        'client' => env('YACAPTCHA_CLIENT'),
    ],
];
