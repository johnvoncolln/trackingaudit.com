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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ups' => [
        'client_id' => env('UPS_CLIENT_ID'),
        'client_secret' => env('UPS_CLIENT_SECRET'),
        'token_url' => env('UPS_TOKEN_URL'),
        'track_url' => env('UPS_TRACK_URL'),
        'transit_url' => env('UPS_TRANSIT_URL'),
    ],

    'usps' => [
        'consumer_key' => env('USPS_CONSUMER_KEY'),
        'consumer_secret' => env('USPS_CONSUMER_SECRET'),
        'token_url' => env('USPS_TOKEN_URL'),
        'track_url' => env('USPS_TRACK_URL'),
    ],

    'fedex' => [
        'api_key' => env('FEDEX_API_KEY'),
        'secret_key' => env('FEDEX_SECRET_KEY'),
        'token_url' => env('FEDEX_TOKEN_URL'),
        'track_url' => env('FEDEX_TRACK_URL'),
    ],

];
