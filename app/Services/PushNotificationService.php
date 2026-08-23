<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    // ─── WebPush factory ─────────────────────────────────────────────────────

    private function makeWebPush(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);
    }

    // ─── Core compartido ──────────────────────────────────────────────────────

    /**
     * Envía $payload a un iterable de suscripciones.
     * Elimina automáticamente las expiradas (410 Gone).
     *
     * @param  iterable<PushSubscription>  $subscriptions
     * @return array [exitosas, fallidas, total]
     */
    private function send(iterable $subscriptions, string $payload): array
    {
        // Convertir a colección/array para poder contar
        $subs = collect($subscriptions);

        if ($subs->isEmpty()) {
            return [0, 0, 0];
        }

        $webPush = $this->makeWebPush();

        foreach ($subs as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'contentEncoding' => 'aesgcm',
                    'keys' => [
                        'p256dh' => $sub->p256dh,
                        'auth'   => $sub->auth,
                    ],
                ]),
                $payload
            );
        }

        $exitosas = 0;
        $fallidas = 0;
        $toDelete = [];

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $exitosas++;
            } else {
                $fallidas++;
                // Si el endpoint ya no es válido (410 Gone), lo eliminamos
                if ($report->isSubscriptionExpired()) {
                    $toDelete[] = $report->getRequest()->getUri()->__toString();
                }
            }
        }

        if (!empty($toDelete)) {
            PushSubscription::whereIn('endpoint', $toDelete)->delete();
        }

        return [$exitosas, $fallidas, $subs->count()];
    }

    // ─── API pública ──────────────────────────────────────────────────────────

    /**
     * Envía a TODAS las suscripciones activas.
     *
     * @return array [exitosas, fallidas, total]
     */
    public function sendToAll(string $payload): array
    {
        return $this->send(PushSubscription::all(), $payload);
    }

    /**
     * Envía a todas las suscripciones de un usuario específico.
     * Si el usuario no tiene suscripciones, retorna [0,0,0] sin instanciar WebPush.
     *
     * @return array [exitosas, fallidas, total]
     */
    public function sendToUser(int $userId, string $payload): array
    {
        $subs = PushSubscription::where('user_id', $userId)->get();

        if ($subs->isEmpty()) {
            return [0, 0, 0];
        }

        return $this->send($subs, $payload);
    }

    /**
     * Envía a una suscripción específica (ej. welcome push al registrarse).
     *
     * @return array [exitosas, fallidas, total]
     */
    public function sendToSubscription(PushSubscription $sub, string $payload): array
    {
        return $this->send([$sub], $payload);
    }
}
