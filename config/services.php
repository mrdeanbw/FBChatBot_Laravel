<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */
    'stripe' => [
        'model'  => \Common\Models\Bot::class,
        'key'    => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_APP_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
        'verify_token'  => env('FACEBOOK_VERIFY_TOKEN'),
    ],

    'pusher' => [
        'app_id'     => env('PUSHER_APP_ID'),
        'app_key'    => env('PUSHER_APP_KEY'),
        'app_secret' => env('PUSHER_APP_SECRET'),
    ],
    
    'slack' => [
        'monitor_webhook' => env('MONITOR_SLACK_WEBHOOK')
    ]

];
