<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Obtener o crear el perfil del usuario autenticado via Firebase.
     */
    public function getProfile(Request $request)
    {
        $uid = $request->header('X-Firebase-UID');
        if (!$uid) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = User::firstOrCreate(
            ['firebase_uid' => $uid],
            [
                'name'     => $request->header('X-User-Name', ''),
                'email'    => $request->header('X-User-Email', ''),
                'password' => bcrypt(str()->random(32)),
            ]
        );

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'apellido'      => $user->apellido,
            'domicilio'     => $user->domicilio,
            'ciudad'        => $user->ciudad,
            'codigo_postal' => $user->codigo_postal,
        ]);
    }

    /**
     * Actualizar el perfil del usuario autenticado via Firebase.
     */
    public function updateProfile(Request $request)
    {
        $uid = $request->header('X-Firebase-UID');
        if (!$uid) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'apellido'      => 'sometimes|nullable|string|max:255',
            'domicilio'     => 'sometimes|nullable|string|max:500',
            'ciudad'        => 'sometimes|nullable|string|max:255',
            'codigo_postal' => 'sometimes|nullable|string|max:20',
        ]);

        $user = User::where('firebase_uid', $uid)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $user->update($request->only([
            'name', 'apellido', 'domicilio', 'ciudad', 'codigo_postal'
        ]));

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'apellido'      => $user->apellido,
            'domicilio'     => $user->domicilio,
            'ciudad'        => $user->ciudad,
            'codigo_postal' => $user->codigo_postal,
        ]);
    }
}
