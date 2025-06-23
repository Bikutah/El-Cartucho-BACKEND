<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Exceptions\CodigoPostalNoEncontradoException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles.producto']);

        // Aplicar filtros
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }

        if ($request->filled('firebase_uid')) {
            $query->where('firebase_uid', 'like', '%' . $request->input('firebase_uid') . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('total_min')) {
            $query->where('total', '>=', floatval($request->input('total_min')));
        }

        if ($request->filled('total_max')) {
            $query->where('total', '<=', floatval($request->input('total_max')));
        }

        // Paginación
        $pedidos = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        // Filtros para la vista
        $filtros = [
            ['name' => 'id', 'placeholder' => 'Buscar por ID del pedido'],
            ['name' => 'firebase_uid', 'placeholder' => 'UID de Usuario'],
            [
                'name' => 'estado',
                'placeholder' => 'Filtrar por estado',
                'type' => 'select',
                'options' => [
                    'pendiente' => 'Pendiente',
                    'pagado' => 'Pagado',
                    'cancelado' => 'Cancelado'
                ]
            ],
            ['name' => 'total_min', 'placeholder' => 'Total mínimo ($)'],
            ['name' => 'total_max', 'placeholder' => 'Total máximo ($)']
        ];

        // Reutilizar renderFila
        $renderFila = function ($pedido) {
            $getEstadoBadge = function ($estado) {
                $badges = [
                    'pendiente' => '<span class="status-badge status-pending">
                    <i class="fas fa-clock"></i><span>Pendiente</span>
                </span>',
                    'pagado' => '<span class="status-badge status-paid">
                    <i class="fas fa-check-circle"></i><span>Pagado</span>
                </span>',
                    'cancelado' => '<span class="status-badge status-cancelled">
                    <i class="fas fa-times-circle"></i><span>Cancelado</span>
                </span>'
                ];
                return $badges[$estado] ?? '<span class="status-badge status-unknown">Desconocido</span>';
            };

            $productosResumen = collect($pedido->detalles)->map(function ($detalle) {
                $nombreProducto = optional($detalle->producto)->nombre ?? 'Producto eliminado';
                return [
                    'nombre' => $nombreProducto,
                    'cantidad' => $detalle->cantidad,
                    'precio' => $detalle->precio_unitario
                ];
            });

            $totalProductos = $productosResumen->sum('cantidad');
            $primerProducto = $productosResumen->first();

            return '
            <div class="table-cell" data-label="ID">
                <span class="table-cell-label">ID:</span>
                <div class="order-id">
                    <span class="order-number">#' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT) . '</span>
                </div>
            </div>
            <div class="table-cell" data-label="Cliente">
                <span class="table-cell-label">Cliente:</span>
                <div class="customer-info">
                    <div class="customer-details">
                        <span class="customer-uid truncate-15" 
                              data-bs-toggle="tooltip" 
                              data-bs-placement="top" 
                              title="' . e($pedido->firebase_uid) . '">
                            ' . e(Str::limit($pedido->firebase_uid, 12)) . '
                        </span>
                    </div>
                </div>
            </div>
            <div class="table-cell" data-label="Estado">
                <span class="table-cell-label">Estado:</span>
                ' . $getEstadoBadge($pedido->estado) . '
            </div>
            <div class="table-cell" data-label="Total">
                <span class="table-cell-label">Total:</span>
                <div class="order-total">
                    <span class="amount">$' . number_format($pedido->total, 2, ',', '.') . '</span>
                    <small class="currency">ARS</small>
                </div>
            </div>
            <div class="table-cell" data-label="Fecha">
                <span class="table-cell-label">Fecha:</span>
                <div class="order-date">
                    <span class="date">' . $pedido->created_at->format('d/m/Y') . '</span>
                    <small class="time">' . $pedido->created_at->format('H:i') . '</small>
                </div>
            </div>
            <div class="table-cell" data-label="Productos">
                <span class="table-cell-label">Productos:</span>
                <div class="products-summary">
                    ' . ($primerProducto ? '
                        <div class="product-item">
                            <span class="product-name">' . e(Str::limit($primerProducto['nombre'], 20)) . '</span>
                            <span class="product-quantity">x' . $primerProducto['cantidad'] . '</span>
                        </div>
                    ' : '<span class="no-products">Sin productos</span>') . '
                    ' . ($totalProductos > 1 ? '
                        <div class="more-products">
                            <span class="more-count">+' . ($totalProductos - 1) . ' más</span>
                        </div>
                    ' : '') . '
                </div>
            </div>';
        };

        if ($request->ajax()) {
            return view('base.partials.tabla', [
                'items' => $pedidos,
                'columnas' => [
                    ['label' => 'ID'],
                    ['label' => 'Cliente'],
                    ['label' => 'Estado'],
                    ['label' => 'Total'],
                    ['label' => 'Fecha'],
                    ['label' => 'Productos']
                ],
                'rutaVer' => 'pedidos.show',
                'rutaImprimir' => 'pedidos.imprimir',
                'renderFila' => $renderFila
            ])->render();
        }

        return view('pedido.pedido_listar', [
            'pedidos' => $pedidos,
            'filtros' => $filtros,
            'renderFila' => $renderFila
        ]);
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

    /*
     * Método para calcular el costo de envío basado en el código postal.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function calcularCostoEnvio($cp)
    {
        // Verificar que el CP exista en Argentina
        $this->validarCodigoPostal($cp);

        // Mock del costo según código postal
        $costoEnvio = random_int(7000, 20000);

        return response()->json([
            'costo_envio' => $costoEnvio,
        ]);
    }

   private function validarCodigoPostal($cp)
    {
        // Consulta a la API
        $response = Http::get("http://api.zippopotam.us/ar/{$cp}");

        // Verifica que la respuesta sea exitosa
        if (!$response->successful()) {
            throw new CodigoPostalNoEncontradoException();
        }

        // Extrae el JSON
        $data = $response->json();

        // Si no contiene el array de lugares o está vacío, también es inválido
        if (!isset($data['places']) || empty($data['places'])) {
            throw new CodigoPostalNoEncontradoException();
        }

        // Todo ok, opcional: podés devolver datos si los querés usar
        return $data;
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
