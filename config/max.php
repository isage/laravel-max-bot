<?php

return [
    'token' => env('MAX_BOT_TOKEN'),

    'base_url' => env('MAX_BASE_URL', 'https://platform-api.max.ru'),

    'timeout' => (int) env('MAX_TIMEOUT', 30),

    'connect_timeout' => (int) env('MAX_CONNECT_TIMEOUT', 10),

    'retry_times' => (int) env('MAX_RETRY_TIMES', 0),

    'retry_sleep' => (int) env('MAX_RETRY_SLEEP', 200),

    'webhook_secret' => env('MAX_WEBHOOK_SECRET'),

    'route' => [
        'enabled' => (bool) env('MAX_ROUTE_ENABLED', true),
        'path' => env('MAX_ROUTE_PATH', '/max/webhook'),
        'middleware' => ['api'],
    ],

    'webhook' => [
        'handler' => env('MAX_WEBHOOK_HANDLER'),
    ],
];