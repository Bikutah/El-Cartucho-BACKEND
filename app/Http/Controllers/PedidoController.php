<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles.producto']);

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        if ($request->filled('firebase_uid')) {
            $query->where('firebase_uid', 'like', '%' . $request->firebase_uid . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('total_min')) {
            $query->where('total', '>=', $request->total_min);
        }

        if ($request->filled('total_max')) {
            $query->where('total', '<=', $request->total_max);
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
                'rutaVer' => 'pedidos.show',
                'rutaImprimir' => 'pedidos.imprimir',
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
    
    public function show($id)
    {
        try {
            $pedido = Pedido::with([
                'detalles' => function ($query) {
                    $query->with(['producto' => function ($query) {
                        $query->with('imagenes');
                    }]);
                }
            ])->findOrFail($id);

            return view('pedido.pedido_show', compact('pedido'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('pedidos.index')
                ->with('error', 'El pedido solicitado no existe.');
        } catch (\Exception $e) {
            return redirect()->route('pedidos.index')
                ->with('error', 'Error al cargar el pedido: ' . $e->getMessage());
        }
    }

    public function imprimir($id)
    {
        try {
            $pedido = Pedido::with([
                'detalles' => function ($query) {
                    $query->with('producto');
                }
            ])->findOrFail($id);

            $pdf = Pdf::loadView('pedido.pedido_imprimir', compact('pedido'))
                ->setPaper('a4', 'portrait');

            return $pdf->stream('pedido_' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT) . '.pdf');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('pedidos.index')
                ->with('error', 'El pedido solicitado no existe.');
        } catch (\Exception $e) {
            return redirect()->route('pedidos.index')
                ->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        $firebaseUid = 'Max Verstappen';

        DB::beginTransaction();

        try {
            $total = 0;
            $detalles = [];

            $pedido = Pedido::create([
                'firebase_uid' => $firebaseUid,
                'estado' => 'pendiente',
                'total' => 0,
            ]);

            foreach ($request->productos as $item) {
                $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->firstOrFail();

                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}", 409);
                }

                $subtotal = $producto->precioUnitario * $item['cantidad'];
                $total += $subtotal;

                $detalles[] = DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precioUnitario
                ]);

                $producto->stock -= $item['cantidad'];
                $producto->save();
            }

            $pedido->update(['total' => $total]);

            $preference = $this->crearPreferenciaMercadoPago($pedido, $detalles);

            DB::commit();

            return response()->json([
                'pedido_id' => $pedido->id,
                'mercado_pago_url' => $preference['init_point'],
                'mercado_pago_id' => $preference['id']
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'No se pudo crear el pedido',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    private function crearPreferenciaMercadoPago(Pedido $pedido, $detalles)
    {
        $items = [];

        foreach ($detalles as $detalle) {
            $items[] = [
                'title' => $detalle->producto->nombre,
                'quantity' => $detalle->cantidad,
                'unit_price' => (float)$detalle->precio_unitario,
                'currency_id' => 'ARS'
            ];
        }

        $response = Http::withToken(env('MERCADO_PAGO_ACCESS_TOKEN'))
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => $items,
                'external_reference' => $pedido->id,
                'back_urls' => [
                    'success' => env('FRONT_URL') . '/pago/success',
                    'failure' => env('FRONT_URL') . '/pago/failure',
                    'pending' => env('FRONT_URL') . '/pago/pending',
                ],
                'notification_url' => 'https://el-cartucho-git-dev-victor-s-projects-2bfad959.vercel.app/ed/webhook/mercadopago',
                'auto_return' => 'approved',
                'statement_descriptor' => 'ELCARTUCHO'
            ]);

        if (!$response->successful()) {
            throw new \Exception($response->json('message') ?? 'Error al crear preferencia', $response->status());
        }

        return $response->json();
    }
}