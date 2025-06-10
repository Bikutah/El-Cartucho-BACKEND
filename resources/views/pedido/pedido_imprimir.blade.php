<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido #{{ str_pad($pedido->id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1, h2, h3 {
            text-align: center;
            margin: 10px 0;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total {
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>El Cartucho</h1>
        <p>Consolas retro, juegos retro y mucho más</p>
        <p>Trelew, Chubut - Argentina</p>
        <p>Teléfono: (0280) 123-4567</p>
        <p>Email: contacto@elcartucho.com</p>
    </div>

    <h2>Pedido #{{ str_pad($pedido->id, 4, '0', STR_PAD_LEFT) }}</h2>
    <p>Fecha: {{ $pedido->created_at->format('d/m/Y H:i') }}</p>
    <p>Estado: {{ ucfirst($pedido->estado) }}</p>

    <h3>Información del Cliente</h3>
    <p>Usuario ID: {{ $pedido->firebase_uid }}</p>
    <p>Fecha del Pedido: {{ $pedido->created_at->format('d/m/Y H:i') }}</p>
    @if($pedido->mercado_pago_id)
        <p>ID MercadoPago: {{ $pedido->mercado_pago_id }}</p>
    @endif

    <h3>Detalle de Productos</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->detalles as $detalle)
                <tr>
                    <td>
                        {{ $detalle->producto->nombre ?? 'Producto eliminado' }}
                        @if($detalle->producto && $detalle->producto->descripcion)
                            <br><small>{{ Str::limit($detalle->producto->descripcion, 60) }}</small>
                        @endif
                    </td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>${{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
                    <td>${{ number_format($detalle->precio_unitario * $detalle->cantidad, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <tr>
            <td class="total">Cantidad de productos:</td>
            <td class="text-right">{{ $pedido->detalles->sum('cantidad') }} items</td>
        </tr>
        <tr>
            <td class="total">Subtotal:</td>
            <td class="text-right">${{ number_format($pedido->detalles->sum(fn($detalle) => $detalle->precio_unitario * $detalle->cantidad), 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="total">TOTAL:</td>
            <td class="text-right">${{ number_format($pedido->total, 2, ',', '.') }} ARS</td>
        </tr>
    </table>

    <div class="footer">
        <p>Gracias por tu compra en El Cartucho</p>
        <p>Este documento fue generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>