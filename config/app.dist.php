<?php

return [
    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'returns',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'openai' => [
        'api_key' => 'PASTE_YOUR_KEY_HERE',
        'base_url' => 'https://api.openai.com/v1',
        'model' => 'gpt-5.0-mini',
        'request_timeout' => 30,
    ],
    'processing' => [
        'chunk_size' => 3,
        'rate_limit_backoff' => [60, 180, 300],
    ],
    'app' => [
        'timezone' => 'Europe/Prague',
    ],
];
