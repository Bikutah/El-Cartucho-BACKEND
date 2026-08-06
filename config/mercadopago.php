<?php

return [
    'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
    'webhook_secret_token' => env('WEBHOOK_SECRET_TOKEN'),
    'front_url' => env('FRONT_URL'),
    'notification_url' => env('NOTIFICATION_URL', 'https://el-cartucho.vercel.app/ed/webhook/mercadopago'),
    'expiration_hours' => (int) env('PEDIDO_EXPIRATION_HOURS', 72),
    'legacy_expiration_hours' => (int) env('PEDIDO_LEGACY_EXPIRATION_HOURS', 96),
];
