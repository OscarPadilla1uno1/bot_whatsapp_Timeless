<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .logo {
            float: right;
            width: 120px;
            height: auto;
        }

        .totales {
            margin-top: 10px;
            width: 300px;
            float: left;
        }
    </style>
</head>

<body>
    <!-- Logo arriba a la derecha -->
    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo del Restaurante" class="logo">

    <h2>Factura Pedido #{{ $pedido->id }}</h2>

    <!-- Datos del cliente -->
    <p><strong>Cliente:</strong> {{ $pedido->cliente->nombre }}</p>
    <p><strong>Teléfono:</strong> {{ $pedido->cliente->telefono }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y H:i') }}</p>

    <!-- Tabla de platillos -->
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

    <!-- Totales alineados a la izquierda -->
    <table class="totales">
        <tr>
            <th>Subtotal:</th>
            <td>LPS. {{ number_format($subtotal, 2) }}</td>
        </tr>
        <tr>
            <th>Envío:</th>
            <td>LPS. {{ number_format($costoEnvio, 2) }}</td>
        </tr>
        <tr>
            <th>Total:</th>
            <td><strong>LPS. {{ number_format($pedido->total, 2) }}</strong></td>
        </tr>
    </table>
</body>

</html>
