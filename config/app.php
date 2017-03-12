<?php

return [
    'name'            => 'MrReply',
    'env'             => env('APP_ENV', 'production'),
    'debug'           => env('APP_DEBUG', false),
    'url'             => env('APP_URL'),
    'timezone'        => 'UTC',
    'locale'          => 'en',
    'fallback_locale' => 'en',
    'key'             => env('APP_KEY'),
    'cipher'          => 'AES-256-CBC',
    'log'             => env('APP_LOG', 'single'),
    'log_level'       => env('APP_LOG_LEVEL', 'debug'),

    'invalid_button_url' => env('INVALID_BUTTON_URL')
];
