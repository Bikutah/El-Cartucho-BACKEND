<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Carrito;
use App\Models\Producto;

class CarritoController extends Controller
{
    private function getUid(Request $request): ?string
    {
        return $request->header('X-Firebase-UID');
    }

    public function index(Request $request)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['error' => 'No autenticado'], 401);

        $items = Carrito::with(['producto.imagenes'])
            ->where('firebase_uid', $uid)
            ->get()
            ->map(function ($item) {
                $producto = $item->producto;
                $imagen = $producto->imagenes->first()?->url ?? null;
                return [
                    'producto_id' => $producto->id,
                    'title'       => $producto->nombre,
                    'price'       => (float) $producto->precioUnitario,
                    'stock'       => $producto->stock,
                    'image'       => $imagen,
                    'quantity'    => $item->cantidad,
                ];
            });

        return response()->json($items);
    }

    public function upsert(Request $request)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['error' => 'No autenticado'], 401);

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad'    => 'required|integer|min:1',
        ]);

        $producto = Producto::findOrFail($request->producto_id);
        $cantidad = min($request->cantidad, $producto->stock);

        $item = Carrito::updateOrCreate(
            ['firebase_uid' => $uid, 'producto_id' => $request->producto_id],
            ['cantidad' => $cantidad]
        );

        return response()->json($item);
    }

    public function removeItem(Request $request, int $productoId)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['error' => 'No autenticado'], 401);

        Carrito::where('firebase_uid', $uid)
            ->where('producto_id', $productoId)
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function clear(Request $request)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['error' => 'No autenticado'], 401);

        Carrito::where('firebase_uid', $uid)->delete();

        return response()->json(['ok' => true]);
    }
}
