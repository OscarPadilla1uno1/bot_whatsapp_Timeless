<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Factura Pedido #{{ $pedido->id }}</h2>

    <p><strong>Cliente:</strong> {{ $pedido->cliente->nombre }}</p>
    <p><strong>Teléfono:</strong> {{ $pedido->cliente->telefono }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Platillo</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->platillo->nombre }}</td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>LPS. {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td>LPS. {{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Total: LPS. {{ number_format($pedido->total, 2) }}</h3>
</body>
</html>
