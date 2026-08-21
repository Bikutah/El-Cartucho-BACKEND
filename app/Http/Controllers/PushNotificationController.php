<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationController extends Controller
{
    // ─── Helpers ─────────────────────────────────────────────────────────────────

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

    // ─── API — rutas autenticadas con firebase ────────────────────────────────

    /**
     * GET /api/push/vapid-key
     * Devuelve la clave pública VAPID para que el frontend pueda suscribirse.
     */
    public function vapidKey()
    {
        return response()->json([
            'vapid_public_key' => config('webpush.vapid.public_key'),
        ]);
    }

    /**
     * POST /api/push/subscribe
     * Guarda (o actualiza) la suscripción del usuario actual.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string|max:2048',
            'keys'     => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth'   => 'required|string',
        ]);

        $user = $request->attributes->get('firebase_user');

        PushSubscription::updateOrCreate(
            [
                'user_id'  => $user->id,
                'endpoint' => $request->endpoint,
            ],
            [
                'p256dh' => $request->input('keys.p256dh'),
                'auth'   => $request->input('keys.auth'),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /api/push/unsubscribe
     * Elimina la suscripción del dispositivo actual.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = $request->attributes->get('firebase_user');

        PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/push/status
     * Informa si el endpoint dado está registrado para este usuario.
     */
    public function status(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);
        $user = $request->attributes->get('firebase_user');

        $exists = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $request->endpoint)
            ->exists();

        return response()->json(['subscribed' => $exists]);
    }

    // ─── Web — rutas admin ────────────────────────────────────────────────────

    /**
     * GET /notificaciones
     * Vista del panel de notificaciones en el admin.
     */
    public function index()
    {
        $totalSuscriptos = PushSubscription::distinct('user_id')->count('user_id');
        $logs = NotificationLog::orderByDesc('created_at')->take(20)->get();

        return view('notificaciones.index', compact('totalSuscriptos', 'logs'));
    }

    /**
     * POST /notificaciones/enviar
     * Envía una notificación personalizada a todos los suscriptos.
     */
    public function enviar(Request $request)
    {
        $request->validate([
            'titulo'  => 'required|string|max:100',
            'mensaje' => 'required|string|max:300',
            'url'     => 'nullable|url|max:500',
        ]);

        $payload = json_encode([
            'title' => $request->titulo,
            'body'  => $request->mensaje,
            'url'   => $request->url ?? '/',
            'icon'  => '/icon.svg',
            'badge' => '/favicon.ico',
        ]);

        [$exitosas, $fallidas, $enviadas] = $this->sendToAllSubscriptions($payload);

        NotificationLog::create([
            'titulo'   => $request->titulo,
            'mensaje'  => $request->mensaje,
            'url'      => $request->url,
            'enviadas' => $enviadas,
            'exitosas' => $exitosas,
            'fallidas' => $fallidas,
            'tipo'     => 'personalizada',
        ]);

        return redirect()->route('notificaciones.index')
            ->with('success', "Notificación enviada: {$exitosas} exitosas, {$fallidas} fallidas.");
    }

    /**
     * POST /notificaciones/nuevo-producto
     * Envía notificación de nuevo producto (llamable desde ProductoController).
     */
    public function sendNuevoProducto(string $nombre, string $url = '/')
    {
        $payload = json_encode([
            'title' => '🎮 ¡Nuevo producto disponible!',
            'body'  => $nombre,
            'url'   => $url,
            'icon'  => '/icon.svg',
            'badge' => '/favicon.ico',
        ]);

        [$exitosas, $fallidas, $enviadas] = $this->sendToAllSubscriptions($payload);

        NotificationLog::create([
            'titulo'   => '¡Nuevo producto disponible!',
            'mensaje'  => $nombre,
            'url'      => $url,
            'enviadas' => $enviadas,
            'exitosas' => $exitosas,
            'fallidas' => $fallidas,
            'tipo'     => 'nuevo_producto',
        ]);
    }

    // ─── Helper privado ───────────────────────────────────────────────────────

    /**
     * Envía $payload a todas las suscripciones activas.
     * Elimina automáticamente las suscripciones expiradas/inválidas.
     * @return array [exitosas, fallidas, total]
     */
    private function sendToAllSubscriptions(string $payload): array
    {
        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            return [0, 0, 0];
        }

        $webPush = $this->makeWebPush();

        foreach ($subscriptions as $sub) {
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

        return [$exitosas, $fallidas, $subscriptions->count()];
    }
}
