<?php

return [
    'domain' => env('HORIZON_DOMAIN', null),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', 'horizon:'),
    'middleware' => ['web'],
    'waits' => [
        'redis:default' => 60,
        'redis:notifications.p1' => 15,
    ],
    'trim' => [
        'recent' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 60,
    ],
    'silenced' => [],
    'env' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['notifications.p1','notifications.default','notifications.email','notifications.digest','default'],
                'balance' => 'auto',
                'maxProcesses' => 10,
                'memory' => 256,
                'tries' => 3,
                'timeout' => 60,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['notifications.p1','notifications.default','notifications.email','notifications.digest','default'],
                'balance' => 'simple',
                'maxProcesses' => 3,
                'memory' => 256,
                'tries' => 2,
                'timeout' => 60,
            ],
        ],
    ],
];

