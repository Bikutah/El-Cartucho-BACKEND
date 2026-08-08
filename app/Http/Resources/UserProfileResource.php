<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transforma el usuario en un array para la respuesta de la API.
     *
     * El email se omite del perfil editable: siempre viene de Firebase.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'firebase_uid'  => $this->firebase_uid,
            'name'          => $this->name,
            'apellido'      => $this->apellido,
            'email'         => $this->email,
            'domicilio'     => $this->domicilio,
            'ciudad'        => $this->ciudad,
            'codigo_postal' => $this->codigo_postal,
            'created_at'    => $this->created_at,
        ];
    }
}
