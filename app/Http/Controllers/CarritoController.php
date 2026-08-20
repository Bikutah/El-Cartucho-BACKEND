<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Producto;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $items = Carrito::with(['producto.imagenes'])
            ->where('user_id', $userId)
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
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad'    => 'required|integer|min:1',
        ]);

        $user               = $request->user();
        $userId             = $user->id;
        $firebaseUid        = $user->firebase_uid;
        $producto           = Producto::findOrFail($request->producto_id);
        $cantidadSolicitada = (int) $request->cantidad;
        $cantidad           = min($cantidadSolicitada, $producto->stock);
        $huboRecorte        = $cantidadSolicitada > $cantidad;

        $item = Carrito::updateOrCreate(
            ['user_id' => $userId, 'producto_id' => $request->producto_id],
            ['firebase_uid' => $firebaseUid, 'cantidad' => $cantidad]
        );

        $responseData = $item->toArray();
        $responseData['cantidad_guardada'] = $cantidad;
        $responseData['hubo_recorte']      = $huboRecorte;

        return response()->json($responseData);
    }

    public function removeItem(Request $request, int $productoId)
    {
        Carrito::where('user_id', $request->user()->id)
            ->where('producto_id', $productoId)
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function clear(Request $request)
    {
        Carrito::where('user_id', $request->user()->id)->delete();

        return response()->json(['ok' => true]);
    }
}
