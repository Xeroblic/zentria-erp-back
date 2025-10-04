<?php

return [
    'base_url' => env('FALABELLA_API_URL', 'https://sellercenter.api.falabella.com'),
    'user_id'  => env('FALABELLA_USER_ID'),
    'api_key'  => env('FALABELLA_API_KEY'),
    'use_mock' => filter_var(env('FALABELLA_USE_MOCK', false), FILTER_VALIDATE_BOOLEAN),
    'version'  => '1.0',
    'format'   => 'JSON',
    'timeout'  => 30,
    'retry_attempts' => 3,
    'retry_delay' => 1000,
];
