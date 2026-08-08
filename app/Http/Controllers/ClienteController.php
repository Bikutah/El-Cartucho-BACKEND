<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'nombre_apellido' => 'nullable|string|max:255',
            'email'           => 'nullable|string|max:255',
            'ciudad'          => 'nullable|string|max:255',
            'ultimo_estado'   => 'nullable|string|in:pendiente,pagado,cancelado',
            'fecha_desde'     => 'nullable|date',
            'fecha_hasta'     => 'nullable|date|after_or_equal:fecha_desde',
        ]);

        session(['listado_url.clientes' => url()->full()]);

        $query = User::query()
            ->withCount('pedidos as total_pedidos')
            ->withSum('pedidos as total_gastado', 'total')
            ->addSelect([
                'ultimo_pedido_fecha' => Pedido::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(1),
                'ultimo_pedido_estado' => Pedido::select('estado')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ]);

        // Filtros
        if ($request->filled('nombre_apellido')) {
            $val = $request->input('nombre_apellido');
            $query->where(function ($q) use ($val) {
                $q->where('name', 'like', "%{$val}%")
                  ->orWhere('apellido', 'like', "%{$val}%");
            });
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->filled('ciudad')) {
            $query->where('ciudad', 'like', '%' . $request->input('ciudad') . '%');
        }

        if ($request->filled('ultimo_estado')) {
            $estado = $request->input('ultimo_estado');
            $query->whereRaw('(SELECT estado FROM pedidos WHERE user_id = users.id ORDER BY created_at DESC, id DESC LIMIT 1) = ?', [$estado]);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereRaw('DATE((SELECT MAX(created_at) FROM pedidos WHERE user_id = users.id)) >= ?', [$request->input('fecha_desde')]);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereRaw('DATE((SELECT MAX(created_at) FROM pedidos WHERE user_id = users.id)) <= ?', [$request->input('fecha_hasta')]);
        }

        $clientes = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $filtros = [
            ['name' => 'nombre_apellido', 'placeholder' => 'Buscar por nombre o apellido'],
            ['name' => 'email', 'placeholder' => 'Buscar por email'],
            ['name' => 'ciudad', 'placeholder' => 'Buscar por ciudad'],
            [
                'name' => 'ultimo_estado',
                'placeholder' => 'Estado del último pedido',
                'type' => 'select',
                'options' => [
                    'pendiente' => 'Pendiente',
                    'pagado'    => 'Pagado',
                    'cancelado' => 'Cancelado',
                ]
            ],
            ['name' => 'fecha_desde', 'placeholder' => 'Último pedido desde', 'type' => 'date'],
            ['name' => 'fecha_hasta', 'placeholder' => 'Último pedido hasta', 'type' => 'date'],
        ];

        $columnas = [
            ['label' => 'Cliente'],
            ['label' => 'Email'],
            ['label' => 'Ciudad'],
            ['label' => 'Pedidos'],
            ['label' => 'Total Gastado'],
            ['label' => 'Último Pedido'],
            ['label' => 'Estado Último'],
        ];

        $renderFila = function ($cliente) {
            $getEstadoBadge = function ($estado) {
                if (!$estado) {
                    return '<span class="status-badge status-unknown">Sin pedidos</span>';
                }
                $badges = [
                    'pendiente' => '<span class="status-badge status-pending"><i class="fas fa-clock"></i><span>Pendiente</span></span>',
                    'pagado'    => '<span class="status-badge status-paid"><i class="fas fa-check-circle"></i><span>Pagado</span></span>',
                    'cancelado' => '<span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i><span>Cancelado</span></span>',
                ];
                return $badges[$estado] ?? '<span class="status-badge status-unknown">' . e($estado) . '</span>';
            };

            $nombreCompleto = trim($cliente->name . ' ' . ($cliente->apellido ?? ''));
            $fechaUltimo = $cliente->ultimo_pedido_fecha ? Carbon::parse($cliente->ultimo_pedido_fecha)->format('d/m/Y H:i') : '-';
            $total = number_format($cliente->total_gastado ?? 0, 2, ',', '.');

            return '
            <div class="table-cell" data-label="Cliente">
                <span class="table-cell-label">Cliente:</span>
                <a href="' . route('clientes.show', $cliente->id) . '" class="text-decoration-none fw-bold text-primary">
                    <i class="fas fa-user-circle me-1"></i>' . e($nombreCompleto) . '
                </a>
            </div>
            <div class="table-cell" data-label="Email">
                <span class="table-cell-label">Email:</span>
                <span class="text-secondary">' . e($cliente->email) . '</span>
            </div>
            <div class="table-cell" data-label="Ciudad">
                <span class="table-cell-label">Ciudad:</span>
                <span>' . e($cliente->ciudad ?? '-') . '</span>
            </div>
            <div class="table-cell" data-label="Pedidos">
                <span class="table-cell-label">Pedidos:</span>
                <span class="badge bg-secondary rounded-pill">' . $cliente->total_pedidos . '</span>
            </div>
            <div class="table-cell" data-label="Total Gastado">
                <span class="table-cell-label">Total Gastado:</span>
                <span class="fw-bold text-success">$' . $total . '</span>
            </div>
            <div class="table-cell" data-label="Último Pedido">
                <span class="table-cell-label">Último Pedido:</span>
                <span class="text-muted">' . $fechaUltimo . '</span>
            </div>
            <div class="table-cell" data-label="Estado Último">
                <span class="table-cell-label">Estado Último:</span>
                ' . $getEstadoBadge($cliente->ultimo_pedido_estado) . '
            </div>';
        };

        if ($request->ajax()) {
            return view('base.partials.tabla', [
                'items'      => $clientes,
                'columnas'   => $columnas,
                'rutaVer'    => 'clientes.show',
                'renderFila' => $renderFila
            ])->render();
        }

        return view('cliente.cliente_listar', [
            'clientes'   => $clientes,
            'filtros'    => $filtros,
            'columnas'   => $columnas,
            'renderFila' => $renderFila
        ]);
    }

    public function show($id)
    {
        $cliente = User::withCount('pedidos as total_pedidos')
            ->withSum('pedidos as total_gastado', 'total')
            ->findOrFail($id);

        $pedidos = $cliente->pedidos()->orderByDesc('created_at')->get();

        $cantidadPedidos = $cliente->total_pedidos;
        $totalGastado = (float)($cliente->total_gastado ?? 0);
        $ticketPromedio = $cantidadPedidos > 0 ? $totalGastado / $cantidadPedidos : 0;
        $primerPedido = $pedidos->last();
        $ultimoPedido = $pedidos->first();

        return view('cliente.cliente_show', compact(
            'cliente',
            'pedidos',
            'cantidadPedidos',
            'totalGastado',
            'ticketPromedio',
            'primerPedido',
            'ultimoPedido'
        ));
    }
}
