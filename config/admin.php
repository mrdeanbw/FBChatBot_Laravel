<?php

return [
    'auth_token'    => env('ADMIN_AUTH_TOKEN'),
    
    'monitor'       => [
        'key'     => env('MONITOR_KEY'),
        'servers' => env('MONITOR_SERVERS'),
    ]
];
