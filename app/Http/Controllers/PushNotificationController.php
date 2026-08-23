<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\NotificationLog;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function __construct(
        private readonly PushNotificationService $pushService
    ) {}

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
     * Después del updateOrCreate, manda un welcome push al dispositivo recién suscrito.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string|max:2048',
            'keys'     => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth'   => 'required|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $sub = PushSubscription::updateOrCreate(
            [
                'user_id'  => $user->id,
                'endpoint' => $request->endpoint,
            ],
            [
                'p256dh' => $request->input('keys.p256dh'),
                'auth'   => $request->input('keys.auth'),
            ]
        );

        // Welcome push al dispositivo recién suscrito.
        // Si falla, la suscripción ya se guardó: el welcome es un extra.
        try {
            $welcomePayload = json_encode([
                'title' => '¡Listo! 🎮',
                'body'  => 'Vas a recibir las novedades de tus pedidos por acá.',
                'url'   => '/',
                'icon'  => '/icon.svg',
                'badge' => '/favicon.ico',
            ]);
            $this->pushService->sendToSubscription($sub, $welcomePayload);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[PushNotif] Welcome push falló (suscripción guardada ok)', [
                'user_id'  => $user->id,
                'endpoint' => $request->endpoint,
                'error'    => $e->getMessage(),
            ]);
        }

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

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

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
        $user = $request->user();
        if (!$user) {
            return response()->json(['subscribed' => false]);
        }

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

        [$exitosas, $fallidas, $enviadas] = $this->pushService->sendToAll($payload);

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

        [$exitosas, $fallidas, $enviadas] = $this->pushService->sendToAll($payload);

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
}
