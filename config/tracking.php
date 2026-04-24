<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tracking Driver
    |--------------------------------------------------------------------------
    |
    | Selects the data source for carrier tracking information.
    |
    | - "direct":   Call carrier APIs (UPS/USPS/FedEx) directly. Updates
    |               arrive by polling via the tracking:update-active command.
    |
    | - "easypost": Route all trackers through the EasyPost Tracker service.
    |               Updates arrive via webhook at POST /webhooks/easypost,
    |               and the polling command becomes a no-op.
    |
    */

    'driver' => env('TRACKING_DRIVER', 'direct'),

    'easypost' => [
        'api_key' => env('EASYPOST_API_KEY'),
        'webhook_secret' => env('EASYPOST_WEBHOOK_SECRET'),
    ],

];
