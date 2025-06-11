<?php

return [

    'paths' => ['api/*', 'ed/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173', 
        'https://elcartucho-n8equiydj-agustinbowens-projects.vercel.app',
        'https://elcartucho.vercel.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
