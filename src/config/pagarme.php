<?php

return [
    'pagarme' => [
        'url'          => env('PAGARME_URL', 'https://api.pagar.me/core/v5'),
        'access_token' => env('PAGARME_ACCESS_TOKEN', 'your-access-token'),
    ],
];
