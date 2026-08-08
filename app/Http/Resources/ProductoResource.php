<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'precio' => $this->precioUnitario,
            'imagen' => $this->primera_imagen?->imagen_url ?? null,
            // # DEPRECADO: eliminar en Pasada C
            'categoria' => $this->categorias->first()?->nombre ?? $this->categoria?->nombre ?? null,
            'categorias' => $this->categorias->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre,
                ];
            })->values()->toArray(),
            'subcategorias' => $this->subcategorias->pluck('nombre'),
            'stock' => $this->stock,
        ];
    }

}
