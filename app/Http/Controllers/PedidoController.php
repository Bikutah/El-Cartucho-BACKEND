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
        $request->validate([
            'id'        => 'nullable|integer|min:1',
            'cliente'   => 'nullable|string|max:255',
            'estado'    => 'nullable|string|in:pendiente,pagado,cancelado',
            'total_min' => 'nullable|numeric|min:0',
            'total_max' => 'nullable|numeric|min:0|gte:total_min',
        ]);

        session(['listado_url.pedidos' => url()->full()]);
        $query = Pedido::with(['user', 'detalles.producto']);

        // Aplicar filtros
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }

        if ($request->filled('cliente')) {
            $val = $request->input('cliente');
            $query->where(function ($q) use ($val) {
                $q->whereHas('user', function ($uq) use ($val) {
                    $uq->where('name', 'like', "%{$val}%")
                       ->orWhere('apellido', 'like', "%{$val}%")
                       ->orWhere('email', 'like', "%{$val}%");
                })->orWhere('firebase_uid', 'like', "%{$val}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('total_min')) {
            $query->where('total', '>=', $request->input('total_min'));
        }

        if ($request->filled('total_max')) {
            $query->where('total', '<=', $request->input('total_max'));
        }

        // Paginación
        $pedidos = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        // Filtros para la vista
        $filtros = [
            ['name' => 'id', 'placeholder' => 'Buscar por ID del pedido'],
            ['name' => 'cliente', 'placeholder' => 'Buscar por nombre, email o UID'],
            [
                'name' => 'estado',
                'placeholder' => 'Filtrar por estado',
                'type' => 'select',
                'options' => [
                    'pendiente' => 'Pendiente',
                    'pagado'    => 'Pagado',
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
                    'pendiente' => '<span class="status-badge status-pending"><i class="fas fa-clock"></i><span>Pendiente</span></span>',
                    'pagado'    => '<span class="status-badge status-paid"><i class="fas fa-check-circle"></i><span>Pagado</span></span>',
                    'cancelado' => '<span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i><span>Cancelado</span></span>'
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

            if ($pedido->user) {
                $nombreCliente = e($pedido->user->name . ($pedido->user->apellido ? ' ' . $pedido->user->apellido : ''));
                $clienteHtml = '<a href="' . route('clientes.show', $pedido->user->id) . '" class="text-decoration-none fw-bold text-primary"><i class="fas fa-user me-1"></i>' . $nombreCliente . '</a>';
            } else {
                $clienteHtml = '<span class="status-badge status-unknown" data-bs-toggle="tooltip" title="UID: ' . e($pedido->firebase_uid) . '"><i class="fas fa-user-slash me-1"></i>Sin cliente asociado</span>';
            }

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
                        ' . $clienteHtml . '
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
                'user',
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

        $user = $request->user();

        DB::beginTransaction();

        try {
            $total = 0;
            $detalles = [];

            // Calcular expiración a partir de config/mercadopago.php
            $expiracion = now()->addHours(config('mercadopago.expiration_hours', 72));

            $pedido = Pedido::create([
                'firebase_uid' => $user->firebase_uid,
                'estado'       => 'pendiente',
                'total'        => 0,
                'expira_at'    => $expiracion,
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

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'No se pudo crear el pedido',
                'message' => $e->getMessage()
            ], $e->getCode() === 409 ? 409 : 500);
        }

        // Llamada HTTP externa FUERA de la transacción principal
        try {
            $detallesCargados = DetallePedido::with('producto')->where('pedido_id', $pedido->id)->get();
            $preference = $this->crearPreferenciaMercadoPago($pedido, $detallesCargados);
        } catch (\Exception $e) {
            // Si la preferencia falla, cancelamos el pedido y reponemos stock en una transacción aparte
            DB::beginTransaction();
            try {
                $pedidoACancelar = Pedido::where('id', $pedido->id)->lockForUpdate()->first();
                if ($pedidoACancelar && $pedidoACancelar->estado === 'pendiente') {
                    $pedidoACancelar->estado = 'cancelado';
                    $pedidoACancelar->save();

                    $detallesAReponer = DetallePedido::where('pedido_id', $pedido->id)->get();
                    foreach ($detallesAReponer as $detalle) {
                        $prod = Producto::where('id', $detalle->producto_id)->lockForUpdate()->first();
                        if ($prod) {
                            $prod->stock += $detalle->cantidad;
                            $prod->save();
                        }
                    }
                }
                DB::commit();
            } catch (\Exception $rollbackException) {
                DB::rollBack();

                $detallesSinReponer = [];
                try {
                    $detallesAReponer = DetallePedido::where('pedido_id', $pedido->id)->get();
                    foreach ($detallesAReponer as $detalle) {
                        $detallesSinReponer[] = "producto_id: {$detalle->producto_id}, cantidad: {$detalle->cantidad}";
                    }
                } catch (\Exception $detailsEx) {
                    $detallesSinReponer[] = "No se pudieron consultar los detalles del pedido";
                }

                Log::critical("FALLO CRÍTICO: No se pudo revertir el stock para el pedido fallido ID: {$pedido->id}", [
                    'pedido_id' => $pedido->id,
                    'detalles_sin_reponer' => $detallesSinReponer,
                    'error_rollback' => $rollbackException->getMessage(),
                    'error_original' => $e->getMessage()
                ]);
            }

            return response()->json([
                'error' => 'No se pudo crear la preferencia de pago',
                'message' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'pedido_id' => $pedido->id,
            'mercado_pago_url' => $preference['init_point'],
            'mercado_pago_id' => $preference['id']
        ], 201);
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

        $response = Http::withToken(config('mercadopago.access_token'))
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => $items,
                'external_reference' => (string)$pedido->id,
                'back_urls' => [
                    'success' => config('mercadopago.front_url') . '/pago/success',
                    'failure' => config('mercadopago.front_url') . '/pago/failure',
                    'pending' => config('mercadopago.front_url') . '/pago/pending',
                ],
                'notification_url' => config('mercadopago.notification_url'),
                'auto_return' => 'approved',
                'statement_descriptor' => 'ELCARTUCHO',
                'expires' => true,
                'expiration_date_to' => $pedido->expira_at->toIso8601String(),
            ]);

        if (!$response->successful()) {
            throw new \Exception($response->json('message') ?? 'Error al crear preferencia', $response->status());
        }

        return $response->json();
    }

    public function misPedidos(Request $request)
    {
        $user = $request->user();

        $pedidos = Pedido::with(['detalles.producto.imagenes'])
            ->where('firebase_uid', $user->firebase_uid)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($pedido) {
                return [
                    'id'         => $pedido->id,
                    'estado'     => $pedido->estado,
                    'total'      => (float) $pedido->total,
                    'created_at' => $pedido->created_at,
                    'productos'  => $pedido->detalles->map(function ($d) {
                        return [
                            'nombre'          => optional($d->producto)->nombre ?? 'Producto eliminado',
                            'cantidad'        => $d->cantidad,
                            'precio_unitario' => (float) $d->precio_unitario,
                            'image'           => optional($d->producto?->imagenes->first())->url,
                        ];
                    }),
                ];
            });

        return response()->json($pedidos);
    }
}
