<?php

return [

    'paths' => ['api/*', 'ed/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173', 
        'http://localhost:5174',
        'http://localhost:5175',
        'https://elcartucho-git-dev-agustinbowens-projects.vercel.app',
        'https://elcartucho.vercel.app',
        'https://elcartucho-87bvepxsa-agustinbowens-projects.vercel.app'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
