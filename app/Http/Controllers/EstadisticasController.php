<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\Categoria;
use Carbon\Carbon;

class EstadisticasController extends Controller
{
    // Método para obtener ventas mensuales (Area Chart)
    public function ventasMensuales()
    {
        $currentYear = Carbon::now()->year;
        
        $pedidos = DB::table('pedidos')
            ->selectRaw('
                EXTRACT(MONTH FROM created_at) as mes_numero,
                COALESCE(SUM(total), 0) as total_ventas
            ')
            ->where('estado', '!=', 'cancelado') // Solo pedidos no cancelados
            ->whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$currentYear])
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('EXTRACT(MONTH FROM created_at)')
            ->get();

        // Crear array completo de 12 meses
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $labels = [];
        $data = [];
        
        // Llenar con datos existentes o ceros
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $meses[$i];
            $pedidoMes = $pedidos->firstWhere('mes_numero', $i);
            $data[] = $pedidoMes ? (float) $pedidoMes->total_ventas : 0;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    // Método para distribución de productos por categoría (Pie Chart)
    public function distribucionProductos()
    {
        $productos = DB::table('productos')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->selectRaw('
                categorias.nombre as categoria,
                COUNT(productos.id) as cantidad
            ')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderBy('cantidad', 'desc')
            ->get();

        $labels = [];
        $data = [];
        
        foreach ($productos as $producto) {
            $labels[] = $producto->categoria;
            $data[] = (int) $producto->cantidad;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    // Método para comparativa anual (Bar Chart)
    public function comparativaAnual()
    {
        $añoActual = Carbon::now()->year;
        $añoInicio = $añoActual - 4; // Últimos 5 años
        
        $pedidos = DB::table('pedidos')
            ->selectRaw('
                EXTRACT(YEAR FROM created_at) as año,
                COALESCE(SUM(total), 0) as total_ventas
            ')
            ->where('estado', '!=', 'cancelado')
            ->whereRaw('EXTRACT(YEAR FROM created_at) >= ?', [$añoInicio])
            ->groupByRaw('EXTRACT(YEAR FROM created_at)')
            ->orderByRaw('EXTRACT(YEAR FROM created_at)')
            ->get();

        $labels = [];
        $data = [];
        
        // Asegurar que todos los años estén representados
        for ($año = $añoInicio; $año <= $añoActual; $año++) {
            $labels[] = (string) $año;
            $pedidoAño = $pedidos->firstWhere('año', $año);
            $data[] = $pedidoAño ? (float) $pedidoAño->total_ventas : 0;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    // Método para categorías por ventas totales (Doughnut Chart)
    public function categorias()
    {
        $categorias = DB::table('detalle_pedido')
            ->join('productos', 'detalle_pedido.producto_id', '=', 'productos.id')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->join('pedidos', 'detalle_pedido.pedido_id', '=', 'pedidos.id')
            ->selectRaw('
                categorias.nombre as categoria,
                COALESCE(SUM(detalle_pedido.cantidad * detalle_pedido.precio_unitario), 0) as total_vendido
            ')
            ->where('pedidos.estado', '!=', 'cancelado')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->havingRaw('SUM(detalle_pedido.cantidad * detalle_pedido.precio_unitario) > 0')
            ->orderBy('total_vendido', 'desc')
            ->get();

        $labels = [];
        $data = [];
        
        foreach ($categorias as $categoria) {
            $labels[] = $categoria->categoria;
            $data[] = (float) $categoria->total_vendido;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    // Método adicional para obtener productos más vendidos
    public function productosMasVendidos()
    {
        $productos = DB::table('detalle_pedido')
            ->join('productos', 'detalle_pedido.producto_id', '=', 'productos.id')
            ->join('pedidos', 'detalle_pedido.pedido_id', '=', 'pedidos.id')
            ->selectRaw('
                productos.nombre as producto,
                SUM(detalle_pedido.cantidad) as total_vendido,
                SUM(detalle_pedido.cantidad * detalle_pedido.precio_unitario) as total_ingresos
            ')
            ->where('pedidos.estado', '!=', 'cancelado')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderBy('total_vendido', 'desc')
            ->limit(10)
            ->get();

        $labels = [];
        $cantidades = [];
        $ingresos = [];
        
        foreach ($productos as $producto) {
            $labels[] = $producto->producto;
            $cantidades[] = (int) $producto->total_vendido;
            $ingresos[] = (float) $producto->total_ingresos;
        }

        return response()->json([
            'labels' => $labels,
            'cantidades' => $cantidades,
            'ingresos' => $ingresos
        ]);
    }

    // Método para estadísticas del dashboard
    public function resumenGeneral()
    {
        $hoy = Carbon::today();
        $mesActual = Carbon::now()->startOfMonth();
        $añoActual = Carbon::now()->startOfYear();

        $resumen = [
            // Ventas de hoy
            'ventas_hoy' => Pedido::whereDate('created_at', $hoy)
                ->where('estado', '!=', 'cancelado')
                ->sum('total'),
            
            // Ventas del mes
            'ventas_mes' => Pedido::where('created_at', '>=', $mesActual)
                ->where('estado', '!=', 'cancelado')
                ->sum('total'),
            
            // Ventas del año
            'ventas_año' => Pedido::where('created_at', '>=', $añoActual)
                ->where('estado', '!=', 'cancelado')
                ->sum('total'),
            
            // Total de productos
            'total_productos' => Producto::count(),
            
            // Productos con stock bajo (menos de 10)
            'productos_stock_bajo' => Producto::where('stock', '<', 10)->count(),
            
            // Pedidos pendientes
            'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
            
            // Categoría más vendida
            'categoria_top' => DB::table('detalle_pedido')
                ->join('productos', 'detalle_pedido.producto_id', '=', 'productos.id')
                ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
                ->join('pedidos', 'detalle_pedido.pedido_id', '=', 'pedidos.id')
                ->selectRaw('categorias.nombre, SUM(detalle_pedido.cantidad) as total_cantidad')
                ->where('pedidos.estado', '!=', 'cancelado')
                ->groupBy('categorias.id', 'categorias.nombre')
                ->orderBy('total_cantidad', 'desc')
                ->first(),
                
            // Promedio de pedidos por día (último mes)
            'promedio_pedidos_dia' => round(
                Pedido::where('created_at', '>=', Carbon::now()->subMonth())
                    ->where('estado', '!=', 'cancelado')
                    ->count() / 30, 2
            )
        ];

        return response()->json($resumen);
    }

    // Método para ventas por estado de pedido
    public function ventasPorEstado()
    {
        $estados = DB::table('pedidos')
            ->selectRaw('
                estado,
                COUNT(*) as cantidad,
                COALESCE(SUM(total), 0) as total_ventas
            ')
            ->groupBy('estado')
            ->orderBy('cantidad', 'desc')
            ->get();

        $labels = [];
        $cantidades = [];
        $totales = [];
        
        foreach ($estados as $estado) {
            $labels[] = ucfirst($estado->estado);
            $cantidades[] = (int) $estado->cantidad;
            $totales[] = (float) $estado->total_ventas;
        }

        return response()->json([
            'labels' => $labels,
            'cantidades' => $cantidades,
            'totales' => $totales
        ]);
    }
}