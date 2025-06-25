<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'https://amc.danwardbautista.com',
        'https://amc2.danwardbautista.com'
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.danwardbautista\.com$#'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
