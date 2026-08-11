<?php

return [
    'expiration_hours' => (int) env('PEDIDO_EXPIRATION_HOURS', 72),
    'legacy_expiration_hours' => (int) env('PEDIDO_LEGACY_EXPIRATION_HOURS', 96),
];
