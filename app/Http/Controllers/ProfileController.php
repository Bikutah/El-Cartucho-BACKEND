<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Obtener o crear el perfil del usuario autenticado vía Firebase.
     *
     * Este endpoint solo requiere token verificado (middleware firebase.token).
     * Tolera que el usuario local no exista aún: lo crea con firstOrCreate.
     * Los datos del usuario vienen del payload verificado, no de headers.
     */
    public function getProfile(Request $request)
    {
        // Los atributos fueron inyectados por VerificarTokenFirebase
        $uid   = $request->attributes->get('firebase_uid');
        $email = $request->attributes->get('firebase_email');
        $name  = $request->attributes->get('firebase_name');

        $user = User::firstOrCreate(
            ['firebase_uid' => $uid],
            [
                'name'     => $name,
                'email'    => $email,
                'password' => bcrypt(Str::random(32)),
            ]
        );

        return new UserProfileResource($user);
    }

    /**
     * Actualizar el perfil del usuario autenticado vía Firebase.
     *
     * Requiere token verificado + usuario local existente (firebase.token + firebase.user).
     * Solo se actualizan los campos editables; el email nunca se modifica desde aquí.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'apellido'      => 'sometimes|nullable|string|max:255',
            'domicilio'     => 'sometimes|nullable|string|max:500',
            'ciudad'        => 'sometimes|nullable|string|max:255',
            'codigo_postal' => 'sometimes|nullable|string|max:20',
        ]);

        $user = $request->user();
        $user->update($request->only([
            'name', 'apellido', 'domicilio', 'ciudad', 'codigo_postal',
        ]));

        return new UserProfileResource($user);
    }
}
