<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    private function getUid(Request $request): ?string
    {
        return $request->header('X-Firebase-UID');
    }

    public function index(Request $request)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['error' => 'No autenticado'], 401);

        $items = Wishlist::with(['producto.imagenes'])
            ->where('firebase_uid', $uid)
            ->get()
            ->map(function ($item) {
                $producto = $item->producto;
                $imagen = $producto->imagenes->first()?->url ?? null;
                return [
                    'wishlist_id'  => $item->id,
                    'producto_id'  => $producto->id,
                    'nombre'       => $producto->nombre,
                    'precio'       => (float) $producto->precioUnitario,
                    'stock'        => $producto->stock,
                    'image'        => $imagen,
                    'created_at'   => $item->created_at,
                ];
            });

        return response()->json($items);
    }

    public function toggle(Request $request)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['error' => 'No autenticado'], 401);

        $request->validate(['producto_id' => 'required|exists:productos,id']);

        $existing = Wishlist::where('firebase_uid', $uid)
            ->where('producto_id', $request->producto_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['action' => 'removed']);
        }

        Wishlist::create([
            'firebase_uid' => $uid,
            'producto_id'  => $request->producto_id,
        ]);

        return response()->json(['action' => 'added']);
    }

    public function check(Request $request, int $productoId)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['in_wishlist' => false]);

        $inWishlist = Wishlist::where('firebase_uid', $uid)
            ->where('producto_id', $productoId)
            ->exists();

        return response()->json(['in_wishlist' => $inWishlist]);
    }

    public function remove(Request $request, int $productoId)
    {
        $uid = $this->getUid($request);
        if (!$uid) return response()->json(['error' => 'No autenticado'], 401);

        Wishlist::where('firebase_uid', $uid)
            ->where('producto_id', $productoId)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
