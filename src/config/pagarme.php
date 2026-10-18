<?php

declare(strict_types = 1);

return [
    'pagarme' => [
        'url'          => env('PAGARME_URL', 'https://api.pagar.me/core/v5'),
        'access_token' => env('PAGARME_ACCESS_TOKEN', 'your-access-token'),
        // Segundos até desistir de uma requisição. Num POST sem
        // Idempotency-Key o pacote não repete: quem chama decide o que fazer
        // (ex.: deixar a cobrança pendente e esperar o webhook).
        'timeout' => (int) env('PAGARME_TIMEOUT', 30),
    ],
];
