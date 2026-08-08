<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Resuelve el usuario local a partir del firebase_uid verificado.
 *
 * Debe ejecutarse DESPUÉS de VerificarTokenFirebase en la cadena de middleware.
 * Si el uid verificado no corresponde a ningún User local, responde 401.
 *
 * GET /profile NO usa este middleware: tolera que el usuario no exista
 * localmente (lo crea él mismo con firstOrCreate).
 */
class RequerirUsuarioLocal
{
    public function handle(Request $request, Closure $next): mixed
    {
        $uid = $request->attributes->get('firebase_uid');

        if (!$uid) {
            // El middleware VerificarTokenFirebase no corrió antes — configuración incorrecta
            return response()->json(['error' => 'Token no verificado.'], 401);
        }

        $user = User::where('firebase_uid', $uid)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado. Iniciá sesión para registrarte.'], 401);
        }

        // Inyectar usuario en el request para que $request->user() funcione en controllers
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
