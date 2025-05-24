<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles.producto']);

        // Filtros opcionales
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        if ($request->filled('firebase_uid')) {
            $query->where('firebase_uid', 'like', '%' . $request->firebase_uid . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $pedidos = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('base.partials.tabla', [
                'items' => $pedidos,
                'columnas' => [
                    ['label' => 'ID'],
                    ['label' => 'Usuario (UID)'],
                    ['label' => 'Estado'],
                    ['label' => 'Total'],
                    ['label' => 'Fecha'],
                    ['label' => 'Productos']
                ],
                'rutaEditar' => null, // si no hay edición
                'renderFila' => function ($pedido) {
                    $productosHtml = collect($pedido->detalles)->map(function ($detalle) {
                        return '<li>' . e($detalle->producto->nombre ?? 'Producto eliminado') . 
                            ' x' . $detalle->cantidad . 
                            ' ($' . number_format($detalle->precio_unitario, 2, ',', '.') . ')</li>';
                    })->implode('');

                    return '
                        <div class="table-cell">' . $pedido->id . '</div>
                        <div class="table-cell">' . e($pedido->firebase_uid) . '</div>
                        <div class="table-cell">' . ucfirst($pedido->estado) . '</div>
                        <div class="table-cell">$' . number_format($pedido->total, 2, ',', '.') . '</div>
                        <div class="table-cell">' . $pedido->created_at->format('d/m/Y H:i') . '</div>
                        <div class="table-cell"><ul class="mb-0">' . $productosHtml . '</ul></div>
                    ';
                }
            ])->render();
        }

        return view('pedido.pedido_listar', compact('pedidos'));
    }
}
