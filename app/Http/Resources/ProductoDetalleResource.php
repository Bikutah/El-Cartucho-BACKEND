<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoDetalleResource extends JsonResource
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
            'imagenes' => $this->imagenes->pluck('imagen_url'),
            'categoria' => $this->categoria?->nombre ?? null,
            'subcategorias' => $this->subcategorias->pluck('nombre'),
            'descripcion' => $this->descripcion,
            'stock' => $this->stock
        ];
    }
}
