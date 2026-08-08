<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $items = Wishlist::with(['producto.imagenes'])
            ->where('user_id', $userId)
            ->get()
            ->map(function ($item) {
                $producto = $item->producto;
                $imagen = $producto->imagenes->first()?->url ?? null;
                return [
                    'wishlist_id' => $item->id,
                    'producto_id' => $producto->id,
                    'nombre'      => $producto->nombre,
                    'precio'      => (float) $producto->precioUnitario,
                    'stock'       => $producto->stock,
                    'image'       => $imagen,
                    'created_at'  => $item->created_at,
                ];
            });

        return response()->json($items);
    }

    public function toggle(Request $request)
    {
        $request->validate(['producto_id' => 'required|exists:productos,id']);

        $userId = $request->user()->id;

        $existing = Wishlist::where('user_id', $userId)
            ->where('producto_id', $request->producto_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['action' => 'removed']);
        }

        Wishlist::create([
            'user_id'    => $userId,
            'producto_id' => $request->producto_id,
        ]);

        return response()->json(['action' => 'added']);
    }

    public function check(Request $request, int $productoId)
    {
        $inWishlist = Wishlist::where('user_id', $request->user()->id)
            ->where('producto_id', $productoId)
            ->exists();

        return response()->json(['in_wishlist' => $inWishlist]);
    }

    public function remove(Request $request, int $productoId)
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('producto_id', $productoId)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
