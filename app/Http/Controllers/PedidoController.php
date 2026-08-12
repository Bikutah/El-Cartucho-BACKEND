<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\PedidoHistorialEstado;
use App\Models\Producto;
use App\Models\ZonaEnvio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CodigoPostalNoEncontradoException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'id'           => 'nullable|integer|min:1',
            'cliente'      => 'nullable|string|max:255',
            'estado_pago'  => 'nullable|string|in:pendiente,pagado,rechazado,expirado,reembolsado',
            'estado_envio' => 'nullable|string|in:sin_preparar,preparando,enviado,entregado,devuelto',
            'total_min'    => 'nullable|numeric|min:0',
            'total_max'    => 'nullable|numeric|min:0|gte:total_min',
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

        if ($request->filled('estado_pago')) {
            $query->where('estado_pago', $request->input('estado_pago'));
        }

        if ($request->filled('estado_envio')) {
            $query->where('estado_envio', $request->input('estado_envio'));
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
                'name'        => 'estado_pago',
                'placeholder' => 'Filtrar por pago',
                'type'        => 'select',
                'options'     => [
                    'pendiente'   => 'Pendiente',
                    'pagado'      => 'Pagado',
                    'rechazado'   => 'Rechazado',
                    'expirado'    => 'Expirado',
                    'reembolsado' => 'Reembolsado',
                ]
            ],
            [
                'name'        => 'estado_envio',
                'placeholder' => 'Filtrar por envío',
                'type'        => 'select',
                'options'     => [
                    'sin_preparar' => 'Sin preparar',
                    'preparando'   => 'Preparando',
                    'enviado'      => 'Enviado',
                    'entregado'    => 'Entregado',
                    'devuelto'     => 'Devuelto',
                ]
            ],
            ['name' => 'total_min', 'placeholder' => 'Total mínimo ($)'],
            ['name' => 'total_max', 'placeholder' => 'Total máximo ($)']
        ];

        // Reutilizar renderFila
        $renderFila = function ($pedido) {
            $getEstadoPagoBadge = function ($estado) {
                $badges = [
                    'pendiente'   => '<span class="status-badge status-pending"><i class="fas fa-clock"></i><span>Pendiente</span></span>',
                    'pagado'      => '<span class="status-badge status-paid"><i class="fas fa-check-circle"></i><span>Pagado</span></span>',
                    'rechazado'   => '<span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i><span>Rechazado</span></span>',
                    'expirado'    => '<span class="status-badge status-cancelled"><i class="fas fa-hourglass-end"></i><span>Expirado</span></span>',
                    'reembolsado' => '<span class="status-badge status-cancelled"><i class="fas fa-undo"></i><span>Reembolsado</span></span>',
                ];
                return $badges[$estado] ?? '<span class="status-badge status-unknown">Desconocido</span>';
            };

            $getEstadoEnvioBadge = function ($estado) {
                if (!$estado) {
                    return '<span class="badge bg-secondary">—</span>';
                }
                $badges = [
                    'sin_preparar' => '<span class="badge bg-warning text-dark"><i class="fas fa-box me-1"></i>Sin preparar</span>',
                    'preparando'   => '<span class="badge bg-info text-dark"><i class="fas fa-dolly me-1"></i>Preparando</span>',
                    'enviado'      => '<span class="badge bg-primary"><i class="fas fa-truck me-1"></i>Enviado</span>',
                    'entregado'    => '<span class="badge bg-success"><i class="fas fa-home me-1"></i>Entregado</span>',
                    'devuelto'     => '<span class="badge bg-danger"><i class="fas fa-undo me-1"></i>Devuelto</span>',
                ];
                return $badges[$estado] ?? '<span class="badge bg-secondary">Desconocido</span>';
            };

            $productosResumen = collect($pedido->detalles)->map(function ($detalle) {
                $nombreProducto = optional($detalle->producto)->nombre ?? 'Producto eliminado';
                return [
                    'nombre'   => $nombreProducto,
                    'cantidad' => $detalle->cantidad,
                    'precio'   => $detalle->precio_unitario
                ];
            });

            $totalProductos = $productosResumen->sum('cantidad');
            $primerProducto = $productosResumen->first();

            $clienteNombre = optional($pedido->user)->name ?? 'Sin cliente asociado';
            $clienteEmail = optional($pedido->user)->email ?? $pedido->email;

            return '
            <div class="table-cell" data-label="ID">
                <span class="table-cell-label">ID:</span>
                <span class="order-id">#' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT) . '</span>
            </div>
            <div class="table-cell" data-label="Cliente">
                <span class="table-cell-label">Cliente:</span>
                <div class="client-info">
                    <span class="client-name">' . e($clienteNombre) . '</span>
                    <small class="client-email">' . e($clienteEmail) . '</small>
                </div>
            </div>
            <div class="table-cell" data-label="Pago">
                <span class="table-cell-label">Pago:</span>
                ' . $getEstadoPagoBadge($pedido->estado_pago) . '
            </div>
            <div class="table-cell" data-label="Envío">
                <span class="table-cell-label">Envío:</span>
                ' . $getEstadoEnvioBadge($pedido->estado_envio) . '
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
                    ['label' => 'Pago'],
                    ['label' => 'Envío'],
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
                'zonaEnvio',
                'historialEstados.user',
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

    public function update(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $request->validate([
            'estado_envio'    => 'required|string',
            'transportista'   => 'nullable|string|max:255',
            'tracking_numero' => 'nullable|string|max:255',
            'observacion'     => 'nullable|string',
        ]);

        try {
            $pedido->cambiarEstadoEnvio(
                $request->input('estado_envio'),
                'panel',
                auth()->id(),
                $request->input('observacion'),
                $request->input('transportista'),
                $request->input('tracking_numero')
            );

            return redirect()->route('pedidos.show', $pedido->id)
                ->with('success', 'Estado de envío actualizado correctamente.');
        } catch (\DomainException $e) {
            return redirect()->route('pedidos.show', $pedido->id)
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('pedidos.show', $pedido->id)
                ->with('error', 'Error al actualizar el estado: ' . $e->getMessage());
        }
    }

    public function imprimir($id)
    {
        try {
            $pedido = Pedido::with([
                'zonaEnvio',
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

    public function calcularCostoEnvio($cp)
    {
        // Verificar que el CP exista en Argentina
        $this->validarCodigoPostal($cp);

        // Resolver la zona de envío según el código postal
        $zona = ZonaEnvio::paraCodigoPostal((string) $cp);

        if (!$zona) {
            return response()->json([
                'error'   => 'No realizamos envíos a ese código postal',
                'message' => 'No realizamos envíos a ese código postal'
            ], 422);
        }

        return response()->json([
            'costo_envio'   => (float) $zona->costo,
            'costo'         => (float) $zona->costo,
            'zona'          => $zona->nombre,
            'zona_envio_id' => $zona->id,
        ]);
    }

    private function validarCodigoPostal($cp)
    {
        // Limpiar el CP manteniendo solo números
        $cpLimpio = preg_replace('/[^0-9]/', '', $cp);

        // CP de Argentina típicamente tienen 4 dígitos
        if (strlen($cpLimpio) < 4) {
            throw new CodigoPostalNoEncontradoException("El código postal debe tener al menos 4 dígitos");
        }

        try {
            $response = Http::timeout(3)
                ->get("http://api.zippopotam.us/ar/{$cpLimpio}");

            if ($response->status() === 404) {
                throw new CodigoPostalNoEncontradoException("El código postal {$cp} no fue encontrado en Argentina");
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("API de código postal no disponible, omitiendo validación: " . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_postal'           => 'required|string',
            'productos'               => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad'    => 'required|integer|min:1',
            'email'                   => 'nullable|email',
            'domicilio'               => 'nullable|string',
            'ciudad'                  => 'nullable|string',
        ]);

        $user = $request->user();
        $cp = $request->input('codigo_postal', $user->codigo_postal ?? '');

        // Resolver la zona de envío antes de iniciar la transacción
        $zona = ZonaEnvio::paraCodigoPostal((string) $cp);

        if (!$zona) {
            return response()->json([
                'error'   => 'No realizamos envíos a ese código postal',
                'message' => 'No realizamos envíos a ese código postal'
            ], 422);
        }

        $costoEnvio = (float) $zona->costo;

        DB::beginTransaction();

        try {
            $totalProductos = 0;
            $detalles = [];

            // Calcular expiración a partir de config/mercadopago.php
            $expiracion = now()->addHours(config('mercadopago.expiration_hours', 72));

            $pedido = Pedido::create([
                'user_id'       => $user->id,
                'firebase_uid'  => $user->firebase_uid,
                'email'         => $request->input('email', $user->email),
                'domicilio'     => $request->input('domicilio', $user->domicilio),
                'ciudad'        => $request->input('ciudad', $user->ciudad),
                'codigo_postal' => $cp,
                'costo_envio'   => $costoEnvio,
                'zona_envio_id' => $zona->id,
                'estado'        => 'pendiente',
                'estado_pago'   => 'pendiente',
                'estado_envio'  => null,
                'total'         => 0,
                'expira_at'     => $expiracion,
            ]);

            PedidoHistorialEstado::create([
                'pedido_id'       => $pedido->id,
                'tipo'            => 'pago',
                'estado_anterior' => null,
                'estado_nuevo'    => 'pendiente',
                'user_id'         => null,
                'origen'          => 'sistema',
                'observacion'     => 'Creación del pedido',
                'created_at'      => now(),
            ]);

            foreach ($request->productos as $item) {
                $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->firstOrFail();

                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}", 409);
                }

                $subtotal = $producto->precioUnitario * $item['cantidad'];
                $totalProductos += $subtotal;

                $detalles[] = DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precioUnitario
                ]);

                $producto->stock -= $item['cantidad'];
                $producto->save();
            }

            $total = $totalProductos + $costoEnvio;
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
            $preference = $this->crearPreferenciaMercadoPago($pedido->load('zonaEnvio'), $detallesCargados);
        } catch (\Exception $e) {
            // Si la preferencia falla, cancelamos el pedido y reponemos stock en una transacción aparte
            DB::beginTransaction();
            try {
                $pedidoACancelar = Pedido::where('id', $pedido->id)->lockForUpdate()->first();
                if ($pedidoACancelar && $pedidoACancelar->estado_pago === 'pendiente') {
                    $pedidoACancelar->cambiarEstadoPago('rechazado', 'sistema');

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

        // Guardamos el preference_id en el pedido para trazabilidad
        $pedido->mercado_pago_preference_id = $preference['id'];
        $pedido->save();

        return response()->json([
            'pedido_id'              => $pedido->id,
            'mercado_pago_url'       => $preference['init_point'],
            'mercado_pago_id'        => $preference['id'],
            'mercado_pago_preference_id' => $preference['id'],
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

        if ($pedido->costo_envio > 0) {
            $nombreZona = optional($pedido->zonaEnvio)->nombre ?? 'Domicilio';
            $items[] = [
                'title'       => "Envío a domicilio ({$nombreZona})",
                'quantity'    => 1,
                'unit_price'  => (float)$pedido->costo_envio,
                'currency_id' => 'ARS'
            ];
        }

        $response = Http::withToken(config('services.mercadopago.access_token'))
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => $items,
                'external_reference' => (string)$pedido->id,
                'back_urls' => [
                    'success' => config('services.mercadopago.front_url') . '/pago/success',
                    'failure' => config('services.mercadopago.front_url') . '/pago/failure',
                    'pending' => config('services.mercadopago.front_url') . '/pago/pending',
                ],
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
                    'id'             => $pedido->id,
                    'estado'         => $pedido->estado,
                    'estado_pago'    => $pedido->estado_pago,
                    'estado_envio'   => $pedido->estado_envio,
                    'estado_visible' => $pedido->estado_visible,
                    'costo_envio'    => (float) $pedido->costo_envio,
                    'tiene_tracking' => !empty($pedido->tracking_numero),
                    'total'          => (float) $pedido->total,
                    'created_at'     => $pedido->created_at,
                    'productos'      => $pedido->detalles->map(function ($d) {
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

    public function detallePedidoCliente(Request $request, $id)
    {
        $user = $request->user();

        $pedido = Pedido::with([
            'zonaEnvio',
            'detalles.producto.imagenes',
            'historialEstados' => function ($q) {
                $q->orderBy('created_at', 'asc');
            }
        ])->find($id);

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado'], 404);
        }

        // Verificar autorización: el pedido debe pertenecer al usuario autenticado (user_id o firebase_uid)
        $perteneceAlUsuario = ($pedido->user_id && $pedido->user_id === $user->id)
            || ($pedido->firebase_uid && $pedido->firebase_uid === $user->firebase_uid);

        if (!$perteneceAlUsuario) {
            return response()->json(['message' => 'Pedido no encontrado'], 404);
        }

        $costoEnvio = (float) $pedido->costo_envio;
        $total = (float) $pedido->total;
        $subtotalProductos = (float) ($total - $costoEnvio);

        $mapearEstadoEtiqueta = function ($tipo, $estadoNuevo) {
            if ($tipo === 'pago') {
                return match ($estadoNuevo) {
                    'pagado'      => 'Pago confirmado',
                    'rechazado'   => 'Pago rechazado',
                    'expirado'    => 'Expirado',
                    'reembolsado' => 'Reembolsado',
                    'pendiente'   => 'Esperando pago',
                    default       => ucfirst($estadoNuevo),
                };
            }
            if ($tipo === 'envio') {
                return match ($estadoNuevo) {
                    'sin_preparar' => 'Pago confirmado',
                    'preparando'   => 'Preparando tu pedido',
                    'enviado'      => 'En camino',
                    'entregado'    => 'Entregado',
                    'devuelto'     => 'Devuelto',
                    default        => ucfirst($estadoNuevo),
                };
            }
            return ucfirst($estadoNuevo);
        };

        $historial = $pedido->historialEstados
            ->filter(function ($item) {
                if ($item->origen === 'sistema') {
                    return false;
                }
                if ($item->tipo === 'pago' && $item->estado_nuevo === 'pendiente') {
                    return false;
                }
                return true;
            })
            ->map(function ($item) use ($mapearEstadoEtiqueta) {
                return [
                    'estado' => $mapearEstadoEtiqueta($item->tipo, $item->estado_nuevo),
                    'fecha'  => $item->created_at,
                ];
            })
            ->values();

        return response()->json([
            'id'                 => $pedido->id,
            'estado_pago'        => $pedido->estado_pago,
            'estado_envio'       => $pedido->estado_envio,
            'estado_visible'     => $pedido->estado_visible,
            'created_at'         => $pedido->created_at,
            'total'              => $total,
            'subtotal_productos' => $subtotalProductos,
            'costo_envio'        => $costoEnvio,
            'zona_envio'         => optional($pedido->zonaEnvio)->nombre ?? 'Sin zona',
            'envio'              => [
                'domicilio'       => $pedido->domicilio,
                'ciudad'          => $pedido->ciudad,
                'codigo_postal'   => $pedido->codigo_postal,
                'email'           => $pedido->email,
                'transportista'   => $pedido->transportista,
                'tracking_numero' => $pedido->tracking_numero,
                'enviado_at'      => $pedido->enviado_at,
                'entregado_at'    => $pedido->entregado_at,
            ],
            'productos'          => $pedido->detalles->map(function ($d) {
                $precioUnitario = (float) $d->precio_unitario;
                $cantidad = (int) $d->cantidad;
                return [
                    'nombre'          => optional($d->producto)->nombre ?? 'Producto eliminado',
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => (float) ($cantidad * $precioUnitario),
                    'imagen'          => optional($d->producto?->imagenes->first())->url,
                ];
            }),
            'historial'          => $historial,
        ]);
    }
}
