<?php
use Illuminate\Http\Request;
use App\Http\Controllers\MercadoPagoWebhookController;

Route::post('/webhook/mercadopago', [MercadoPagoWebhookController::class, 'handle']);
