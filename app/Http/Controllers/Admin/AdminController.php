<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Platillo;
use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\MenuDiario;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\EnvioGratisFecha;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\VroomController;



class AdminController extends Controller
{

    public function todayDate(Request $request)
    {
        return response()->json(['fecha' => Carbon::today()->toDateString()]);
    }

    public function verificar(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date_format:Y-m-d',
        ]);

        $fecha = Carbon::parse($request->input('fecha'))->startOfDay();
        $aplicaEnvioGratis = EnvioGratisFecha::tieneEnvioGratisParaFecha($fecha);

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'envio_gratis' => $aplicaEnvioGratis,
        ]);
    }

    public function estadoEnvioGratis($fecha)
    {
        $registro = EnvioGratisFecha::where('fecha', $fecha)->first();

        if (!$registro) {
            return response()->json(['existe' => false]);
        }

        return response()->json([
            'existe' => true,
            'activo' => $registro->activo,
            'cantidad_minima' => $registro->cantidad_minima,
        ]);
    }

    public function actualizarEnvioGratis(Request $request, $fecha)
    {
        $registro = EnvioGratisFecha::where('fecha', $fecha)->firstOrFail();

        $registro->activo = $request->boolean('activo');
        $registro->cantidad_minima = $request->input('cantidad_minima', 3);
        $registro->save();

        return response()->json(['success' => true]);
    }


    public function descargarFactura($id)
    {
        $pedido = Pedido::with(['cliente', 'detalles.platillo'])->findOrFail($id);

        $subtotal = $pedido->detalles->sum(function ($detalle) {
            return $detalle->cantidad * $detalle->precio_unitario;
        });

        $costoEnvio = $pedido->total - $subtotal;

        $pdf = PDF::loadView('pdf.factura', compact('pedido', 'subtotal', 'costoEnvio'));
        return $pdf->download("factura_pedido_{$pedido->id}.pdf");
    }

    public function obtenerFacturaPDF($id)
    {
        $pedido = Pedido::with(['cliente', 'detalles.platillo'])->findOrFail($id);

        // Calcular el total de productos
        $subtotal = $pedido->detalles->sum(function ($detalle) {
            return $detalle->cantidad * $detalle->precio_unitario;
        });

        // Calcular el costo de envío
        $envio = $pedido->total - $subtotal;

        // Pasar también el total de productos y envío a la vista
        $pdf = PDF::loadView('pdf.factura', [
            'pedido' => $pedido,
            'subtotal' => $subtotal,
            'costoEnvio' => $envio,
        ]);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="factura_pedido_' . $pedido->id . '.pdf"');
    }



    ///////////////////////////////para el menu del dia
    public function paginaMenuDelDia(Request $request)
    {
        // Si se recibe una fecha en el query, obtener el menú para esa fecha
        if ($request->has('fecha')) {
            $fecha = $request->query('fecha');
            $platillosEnMenu = DB::select('CALL obtener_menu_diario_por_fecha(?)', [$fecha]);
        } else {
            // Si no, se obtiene el menú para el día de hoy
            $platillosEnMenu = DB::select('CALL obtener_menu_diario()');
        }

        // Llamamos a otro procedimiento o consulta para obtener todos los platillos disponibles
        $todosLosPlatillos = DB::select('CALL ObtenerTodosLosPlatillos()');

        // Retornamos la vista pasando los datos
        return view('admin.menu-diario', compact('platillosEnMenu', 'todosLosPlatillos'));
    }


    public function agregarPlatillo(Request $request)
    {
        $request->validate([
            'platillo_id' => 'required|exists:platillos,id',
            'cantidad' => 'required|integer|min:1',
            'fecha' => 'required|date'
        ]);

        DB::statement('CALL actualizar_menu_diario(?, ?, ?)', [
            $request->platillo_id,
            $request->cantidad,
            $request->fecha
        ]);

        $fecha = Carbon::parse($request->fecha);

        // Solo se crea el registro si aún no existe
        EnvioGratisFecha::establecerSiNoExiste($fecha);

        return response()->json([
            'success' => true
        ]);
    }

    public function obtenerMenuHoy(Request $request)
    {
        $platillosEnMenu = DB::select('CALL obtener_menu_diario()');

        return response()->json([
            'menu' => $platillosEnMenu
        ]);

    }


    public function obtenerMenuPorFecha(Request $request)
    {
        $fecha = $request->query('fecha');
        $platillosEnMenu = DB::select('CALL obtener_menu_diario_por_fecha(?)', [$fecha]);

        return response()->json([
            'platillos' => $platillosEnMenu
        ]);
    }

    public function eliminarPlatillo(Request $request)
    {
        $request->validate([
            'platillo_id' => 'required|integer',
            'fecha' => 'required|date'
        ]);

        DB::statement('CALL eliminar_platillo_menu(?, ?)', [
            $request->platillo_id,
            $request->fecha
        ]);

        return response()->json(['success' => true]);
    }

    public function actualizarCantidad(Request $request)
    {
        $request->validate([
            'platillo_id' => 'required|integer',
            'cantidad' => 'required|integer|min:1',
            'fecha' => 'required|date'
        ]);

        try {
            $result = DB::select('CALL actualizar_cantidad_menu(?, ?, ?)', [
                $request->platillo_id,
                $request->cantidad,
                $request->fecha
            ]);

            return response()->json([
                'success' => true,
                'message' => $result[0]->mensaje ?? 'Cantidad actualizada.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    ///////////////////Para los platillos

    public function obtenerTodosPlatillos(Request $request)
    {
        // Obtiene la página desde la URL si existe
        $page = $request->query('page', 1);

        $platillos = Platillo::where('activo', true)
            ->orderBy('nombre')
            ->paginate(10);

        // Devuelve tanto los datos de los platillos como la paginación en formato HTML
        return response()->json([
            'success' => true,
            'platillos' => $platillos,
        ]);
    }



    // El resto de tus métodos pueden permanecer igual
    // Vista principal del catálogo de platillos
    public function vistaPlatillos(Request $request)
    {
        $search = $request->input('search');

        $platillos = Platillo::where('activo', true)
            ->when($search, function ($query, $search) {
                $search = strtolower($search);
                return $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(descripcion) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(precio_base AS CHAR) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderBy('nombre')
            ->paginate(10);

        return view('admin.platillos', compact('platillos'));
    }

    // Crear platillo
    public function crearPlatillo(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'precio_base' => 'required|numeric|min:1',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048', // Validación para imagen
        ]);

        // Guardar la imagen, si se ha subido
        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $imagenUrl = $imagen->storeAs('platillos', uniqid() . '.' . $imagen->getClientOriginalExtension(), 'public');
        }

        // Llamar al procedimiento almacenado para insertar el platillo
        DB::statement('CALL InsertarPlatillo(?, ?, ?, ?)', [
            $request->nombre,
            $request->descripcion,
            $request->precio_base,
            $imagenUrl, // Guardar la ruta de la imagen
        ]);

        return response()->json(['success' => true]);
    }


    // Actualizar platillo
    public function actualizarPlatillo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:platillos,id',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'precio_base' => 'required|numeric|min:1',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Validación de imagen
            'eliminar_imagen' => 'nullable|boolean', // Campo para indicar si se eliminará la imagen
        ]);

        // Obtener el platillo actual
        $platillo = Platillo::find($request->id);

        // Si el usuario ha decidido eliminar la imagen, eliminamos la imagen actual
        if ($request->has('eliminar_imagen') && $request->eliminar_imagen) {
            // Aquí eliminamos la imagen de almacenamiento
            if ($platillo->imagen_url) {
                Storage::disk('public')->delete($platillo->imagen_url);
            }
            $imagen_url = null;
        } else {
            // Si hay una nueva imagen, la procesamos
            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $imagen_url = $imagen->storeAs('platillos', uniqid() . '.' . $imagen->getClientOriginalExtension(), 'public'); // Guardamos la imagen en la carpeta 'platillos' // Guarda la imagen y obtiene la URL

                // Si ya había una imagen previa, la eliminamos
                if ($platillo->imagen_url) {
                    Storage::disk('public')->delete($platillo->imagen_url);
                }
            } else {
                // Si no hay nueva imagen, mantenemos la imagen actual
                $imagen_url = $platillo->imagen_url;
            }
        }

        // Llamamos al procedimiento almacenado
        DB::statement('CALL sp_actualizar_platillo(?, ?, ?, ?, ?)', [
            $request->id,
            $request->nombre,
            $request->descripcion,
            $request->precio_base,
            $imagen_url, // Enviamos la URL de la imagen (null si no hay imagen)
        ]);

        return response()->json(['success' => true]);
    }


    // Eliminar platillo
    public function eliminarPlatilloCatalogo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:platillos,id'
        ]);

        // Obtener el platillo actual
        $platillo = Platillo::find($request->id);

        if ($platillo->imagen_url) {
            Storage::disk('public')->delete($platillo->imagen_url);
        }

        DB::statement('CALL sp_borrar_platillo(?)', [$request->id]);

        return response()->json(['success' => true]);
    }



    /////////////////////// Para el bot
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371; // Radio de la tierra en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distancia = $radioTierra * $c;

        return $distancia;
    }

    public function cancelarPedidoRepartidor($id)
    {
        try {
            $pedido = Pedido::with(['detalles', 'pago'])->findOrFail($id);

            // Validar que el pedido esté en estado "pendiente"
            if ($pedido->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Solo se pueden cancelar pedidos en estado pendiente.'
                ], 400);
            }

            // Validar que la fecha del pedido sea hoy
            $fechaPedido = Carbon::parse($pedido->fecha_pedido)->toDateString();
            $hoy = Carbon::now('America/Tegucigalpa')->toDateString();

            if ($fechaPedido !== $hoy) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Solo se pueden cancelar pedidos programados para hoy.'
                ], 400);
            }

            // Transacción para cancelar el pedido, reembolsar y restaurar el stock
            DB::transaction(function () use ($pedido, $fechaPedido) {
                foreach ($pedido->detalles as $detalle) {
                    $menuItem = MenuDiario::where('fecha', $fechaPedido)
                        ->where('platillo_id', $detalle->platillo_id)
                        ->first();

                    if ($menuItem) {
                        $menuItem->cantidad_disponible += $detalle->cantidad;
                        $menuItem->save();
                    }
                }

                // Marcar el pedido como cancelado
                $pedido->estado = 'cancelado';
                $pedido->save();

                // Si hay un pago asociado, actualizarlo a reembolsado
                if ($pedido->pago) {
                    $pedido->pago->estado_pago = 'reembolsado';
                    $pedido->pago->save();
                }
            });

            return response()->json([
                'success' => true,
                'mensaje' => 'Pedido cancelado, stock restaurado y pago marcado como reembolsado.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al cancelar el pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function prepararPedido($id)
    {
        try {
            $pedido = Pedido::findOrFail($id);

            // 1. Verificar que el estado del pedido sea "pendiente"
            if ($pedido->estado !== 'pendiente') {
                return response()->json([
                    'mensaje' => 'El pedido no está en estado pendiente.',
                    'codigo' => 1
                ], 400);
            }

            // 2. Verificar que la fecha del pedido sea hoy (ignorando la hora)
            $fechaPedido = Carbon::parse($pedido->fecha_pedido)->toDateString();
            $hoy = Carbon::now('America/Tegucigalpa')->toDateString();

            if ($fechaPedido !== $hoy) {
                return response()->json([
                    'mensaje' => 'El pedido no es del día de hoy.',
                    'codigo' => 2
                ], 400);
            }

            // 3. Actualizar el estado a "en preparación"
            $pedido->estado = 'en preparación';
            $pedido->save();

            return response()->json([
                'mensaje' => 'El pedido fue actualizado a "en preparación" correctamente.',
                'codigo' => 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Ocurrió un error al actualizar el pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verificarNumero(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string'
        ]);

        $cliente = Cliente::where('telefono', $request->telefono)->first();

        if ($cliente) {
            return response()->json([
                'existe' => true,
                'nombre' => $cliente->nombre
            ]);
        } else {
            return response()->json([
                'existe' => false,
                'mensaje' => 'Cliente no encontrado'
            ]);
        }
    }

    public function storePedido(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'telefono' => 'required|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'platillos' => 'required|array',
            'platillos.*.id' => 'required|integer',
            'platillos.*.cantidad' => 'required|integer',
            'metodo_pago' => 'required|string|in:tarjeta,efectivo,transferencia',
            'domicilio' => 'required|boolean',
            'notas' => 'nullable|string',
        ]);

        $nombre = $request->nombre;
        $telefono = $request->telefono;
        $latitud = $request->latitud;
        $longitud = $request->longitud;
        $platillos = $request->platillos;
        $cantidadPlatillos = 0;
        $metodo_pago = strtolower($request->metodo_pago);
        $domicilio = $request->boolean('domicilio');
        $notas = $request->input('notas', null);

        foreach ($platillos as $i) {

            $cantidadPlatillos += $i['cantidad'];

        }


        $hoy = now()->setTimezone('America/Tegucigalpa')->format('Y-m-d H:i:s'); // Ajusta tu zona horaria

        Log::info("Fecha generada: " . $hoy);
        $subtotal = 0.00;

        $distancia_km = 0;
        $tiempo_min = 0;

        $request = new Request([
            'target_lat' => (float) $latitud,
            'target_lng' => (float) $longitud
        ]);

        $response = app(VroomController::class)->calculateDistanceFromVehicle($request);
        $datosDistancia = $response->getData(true);



        // Fallback por si algo falla
        if (!isset($datosDistancia['success']) || !$datosDistancia['success']) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al calcular la distancia y el tiempo estimado',
                'total_final' => 0.00
            ], 400);
        }

        $datos = $datosDistancia['data'] ?? [];

        $distancia_km = $datos['route_info']['distance']['km'] ?? 0;
        $tiempo_min = $datos['route_info']['adjusted_delivery_time']['adjusted_time']['minutes'] ?? 0;

        // Coordenadas del restaurante
        $lat_restaurante = 14.107193046832785;
        $lon_restaurante = -87.1824026712528;

        // Calcular distancia con método interno (como respaldo y lógica extra)
        $distanciaManual = $this->calcularDistancia($lat_restaurante, $lon_restaurante, $latitud, $longitud);
        Log::info("Distancia manual calculada: {$distanciaManual} km");
        Log::info("Distancia calculada: {$distancia_km} km");

        // Obtener día de la semana (0=domingo, 6=sábado)
        $diaSemana = now()->setTimezone('America/Tegucigalpa')->dayOfWeek;
        Log::info("Día de la semana: {$diaSemana}");

        $fechaCarbon = Carbon::parse($hoy);
        $aplicaEnvioGratis = EnvioGratisFecha::tieneEnvioGratisParaFecha($fechaCarbon);

        if (!$domicilio) {
            $costo_envio = 0;
        } else {
            if ($aplicaEnvioGratis && $cantidadPlatillos >= $aplicaEnvioGratis->cantidad_minima) {
                $costo_envio = 0;
            } else {
                if ($distancia_km <= 0.7) {
                    $costo_envio = 0;
                } elseif ($distancia_km > 0.7 && $distancia_km <= 6.0) {
                    $costo_envio = 40;
                } elseif ($distancia_km > 6.0 && $distancia_km <= 6.75) {
                    $costo_envio = 50;
                } elseif ($distancia_km > 6.75 && $distancia_km <= 9.0) {
                    $costo_envio = 70;
                } else {
                    $costo_envio = 80;
                }
            }
        }

        // Log para depuración
        Log::info("Valor del envio para {$hoy}: L.{$costo_envio}");

        // Verificar que hay platillos
        if (empty($platillos)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error: No hay platillos en el pedido',
                'total_final' => 0.00
            ], 400);
        }

        try {
            $result = DB::transaction(function () use ($notas, $domicilio, $nombre, $telefono, $latitud, $longitud, $platillos, $costo_envio, $hoy, &$subtotal, $metodo_pago) {
                // Verificar si el cliente ya existe o crearlo
                $cliente = Cliente::firstOrCreate(
                    ['telefono' => $telefono],
                    ['nombre' => $nombre]
                );

                $estadoPedido = in_array($metodo_pago, ['efectivo', 'transferencia'])
                    ? 'en preparación'
                    : 'pendiente';


                // Crear el pedido
                $pedido = new Pedido([
                    'cliente_id' => $cliente->id,
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'domicilio' => $domicilio,
                    'fecha_pedido' => $hoy,
                    'total' => 0.00, // Se actualizará al final
                    'estado' => $estadoPedido,
                    'notas' => $notas,
                ]);

                $pedido->save();

                // Procesar cada platillo
                foreach ($platillos as $item) {
                    $platilloId = $item['id'];
                    $cantidad = $item['cantidad'];

                    // Verificar existencia en menú diario y disponibilidad
                    $menuItem = MenuDiario::where('fecha', $hoy)
                        ->where('platillo_id', $platilloId)
                        ->first();

                    if (!$menuItem) {
                        throw new \Exception("Error: el platillo ID {$platilloId} no está en el menú de hoy.");
                    }

                    if ($cantidad > $menuItem->cantidad_disponible) {
                        throw new \Exception("Error: no hay suficientes unidades disponibles del platillo ID {$platilloId}.");
                    }

                    // Obtener el precio del platillo
                    $platillo = Platillo::find($platilloId);
                    if (!$platillo) {
                        throw new \Exception("Error: el platillo ID {$platilloId} no existe.");
                    }

                    // Insertar detalle del pedido
                    $detalle = new DetallePedido([
                        'pedido_id' => $pedido->id,
                        'platillo_id' => $platilloId,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $platillo->precio_base
                    ]);

                    $detalle->save();

                    // Actualizar stock en menú
                    $menuItem->cantidad_disponible -= $cantidad;
                    $menuItem->save();

                    // Sumar al subtotal
                    $subtotal += ($cantidad * $platillo->precio_base);
                }

                // Actualizar el total del pedido
                $pedido->total = $subtotal + $costo_envio;
                $pedido->save();

                if ($metodo_pago === 'tarjeta') {
                    $pago = new Pago([
                        'pedido_id' => $pedido->id,
                        'metodo_pago' => 'tarjeta',
                        'estado_pago' => 'pendiente',
                        'fecha_pago' => null,
                        'referencia_transaccion' => null,
                        'request_id' => null,
                        'process_url' => null,
                        'metodo_interno' => null,
                        'canal' => 'whatsapp',
                        'observaciones' => 'Pago con tarjeta pendiente de confirmación'
                    ]);
                } else {
                    // 💵 Pago en efectivo → confirmado automáticamente
                    $pago = new Pago([
                        'pedido_id' => $pedido->id,
                        'metodo_pago' => $metodo_pago,
                        'estado_pago' => 'confirmado',
                        'fecha_pago' => now()->setTimezone('America/Tegucigalpa'),
                        'referencia_transaccion' => null,
                        'request_id' => null,
                        'process_url' => null,
                        'metodo_interno' => null,
                        'canal' => 'whatsapp',
                        'observaciones' => $metodo_pago === 'efectivo'
                            ? 'Pago confirmado en efectivo'
                            : 'Pago confirmado por transferencia'
                    ]);
                }

                $pago->save();


                return [
                    'mensaje' => 'Pedido registrado exitosamente.',
                    'total_final' => $subtotal,
                    'pedido_id' => $pedido->id
                ];
            });

            $total_final = $result['total_final'] + $costo_envio;

            return response()->json([
                'success' => true,
                'mensaje' => $result['mensaje'],
                'total' => $total_final,
                'envio' => $costo_envio,
                'id' => $result['pedido_id']
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => $e->getMessage(),
                'total_final' => 0.00
            ], 400);
        }
    }

    public function cotizarPedido(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'telefono' => 'required|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'platillos' => 'required|array',
            'platillos.*.id' => 'required|integer',
            'platillos.*.cantidad' => 'required|integer',
        ]);

        $latitud = $request->latitud;
        $longitud = $request->longitud;
        $platillos = $request->platillos;
        $cantidadPlatillos = collect($platillos)->sum('cantidad');

        $hoy = now()->setTimezone('America/Tegucigalpa')->format('Y-m-d');
        $distancia_km = 0;
        $tiempo_min = 0;

        $distanceRequest = new Request([
            'target_lat' => (float) $latitud,
            'target_lng' => (float) $longitud
        ]);

        $response = app(VroomController::class)->calculateDistanceFromVehicle($distanceRequest);
        $datosDistancia = $response->getData(true);

        if (!isset($datosDistancia['success']) || !$datosDistancia['success']) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al calcular la distancia y el tiempo estimado',
            ], 400);
        }

        $datos = $datosDistancia['data'] ?? [];
        $distancia_km = $datos['route_info']['distance']['km'] ?? 0;

        $fechaCarbon = Carbon::parse($hoy);
        $aplicaEnvioGratis = EnvioGratisFecha::tieneEnvioGratisParaFecha($fechaCarbon);

        if ($aplicaEnvioGratis && $cantidadPlatillos >= $aplicaEnvioGratis->cantidad_minima) {
            $costo_envio = 0;
        } else {
            if ($distancia_km <= 0.7) {
                $costo_envio = 0;
            } elseif ($distancia_km <= 6.0) {
                $costo_envio = 40;
            } elseif ($distancia_km <= 6.75) {
                $costo_envio = 50;
            } elseif ($distancia_km <= 9.0) {
                $costo_envio = 70;
            } else {
                $costo_envio = 80;
            }
        }

        $detallePlatillos = [];
        $subtotalBase = 0;
        $totalISV = 0;
        $totalPlatillosConISV = 0;

        foreach ($platillos as $item) {
            $platilloId = $item['id'];
            $cantidad = $item['cantidad'];

            $menuItem = MenuDiario::where('fecha', $hoy)
                ->where('platillo_id', $platilloId)
                ->first();

            if (!$menuItem) {
                return response()->json([
                    'success' => false,
                    'mensaje' => "Error: el platillo ID {$platilloId} no está en el menú de hoy.",
                ], 400);
            }

            $platillo = Platillo::find($platilloId);
            if (!$platillo) {
                return response()->json([
                    'success' => false,
                    'mensaje' => "Error: el platillo ID {$platilloId} no existe.",
                ], 400);
            }

            $precio = $platillo->precio_base;
            $isv_unitario = round($precio * 0.12, 2);
            $base_unitaria = round($precio - $isv_unitario, 2);

            $total_unitario = round($precio, 2);
            $subtotal_item = $total_unitario * $cantidad;
            $subtotalBase += $base_unitaria * $cantidad;
            $totalISV += $isv_unitario * $cantidad;
            $totalPlatillosConISV += $subtotal_item;

            $detallePlatillos[] = [
                'nombre' => $platillo->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $total_unitario,
                'base_unitaria' => $base_unitaria,
                'isv_unitario' => $isv_unitario,
                'subtotal' => $subtotal_item,
                'subtotal_base' => $base_unitaria * $cantidad,
                'subtotal_isv' => $isv_unitario * $cantidad
            ];
        }

        $totalFinal = $totalPlatillosConISV + $costo_envio;

        return response()->json([
            'success' => true,
            'mensaje' => 'Cotización generada exitosamente.',
            'detalle_platillos' => $detallePlatillos,
            'resumen' => [
                'subtotal_base' => round($subtotalBase, 2),
                'total_isv' => round($totalISV, 2),
                'total_platillos_con_isv' => round($totalPlatillosConISV, 2),
                'envio' => round($costo_envio, 2),
                'total_general' => round($totalFinal, 2)
            ]
        ]);
    }


    public function procesarComprobante(Request $request)
    {
        $request->validate([
            'comprobante' => 'required|image|max:4096', // PNG, JPG, etc.
        ]);

        $path = $request->file('comprobante')->store('comprobantes_temp');

        // Ejecutar OCR
        $ocr = new TesseractOCR(storage_path("app/$path"));
        $texto = $ocr->run();

        // Extraer datos del texto con regex
        $monto = $this->extraerMonto($texto);
        $fecha = $this->extraerFecha($texto);
        $referencia = $this->extraerReferencia($texto);
        $banco = $this->extraerBanco($texto);

        // Eliminar imagen temporal
        Storage::delete($path);

        return response()->json([
            'monto' => $monto,
            'fecha' => $fecha,
            'referencia' => $referencia,
            'banco' => $banco,
            'texto_crudo' => $texto // útil para debug
        ]);
    }

    private function extraerMonto($texto)
    {
        if (preg_match('/([L\$]?\s?\d+[.,]?\d+)+/i', $texto, $matches)) {
            return $matches[0];
        }
        return null;
    }

    private function extraerFecha($texto)
    {
        if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $texto, $matches)) {
            return $matches[0];
        }
        return null;
    }

    private function extraerReferencia($texto)
    {
        if (preg_match('/Ref(erencia)?:?\s*([A-Z0-9]+)/i', $texto, $matches)) {
            return $matches[2] ?? null;
        }
        return null;
    }

    private function extraerBanco($texto)
    {
        if (stripos($texto, 'BAC') !== false)
            return 'BAC';
        if (stripos($texto, 'Atlántida') !== false)
            return 'Atlántida';
        if (stripos($texto, 'Banpaís') !== false)
            return 'Banpaís';
        return 'Desconocido';
    }




    ////////////////////////////// Para el CRUD de los usuarios

    public function vistaUsuarios()
    {
        $usuarios = User::with('permissions') // Carga permisos con eager loading
            ->select('id', 'name', 'email')
            ->paginate(10); // 10 usuarios por página
        $permisos = DB::select('CALL sp_obtener_permisos()');
        return view('admin.users', compact('usuarios', 'permisos'));
    }


    public function UserStore(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'permiso' => ['required', 'exists:permissions,name'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->givePermissionTo($request->permiso);

        return response()->json(['message' => 'Usuario creado con éxito.'], 201);
    }

    public function showUser($id)
    {
        $user = User::with('permissions')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'permiso' => $user->getPermissionNames()->first(), // o como lo gestiones
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'permiso' => 'required|exists:permissions,name',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update([
                'name' => $request->name,
                'email' => strtolower($request->email),
            ]);

            $user->syncPermissions([$request->permiso]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyUser($id)
    {
        try {

            if (auth()->id() == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario.'
                ], 403);
            }

            $usuario = User::findOrFail($id);

            // Si querés también quitar sus permisos:
            $usuario->syncPermissions([]);

            $usuario->delete();

            return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar el usuario.']);
        }
    }


    public function pedidosStatusView(Request $request)
    {
        $tab = $request->query('tab', 'hoy'); // Por defecto "hoy"

        $query = Pedido::with('cliente')->orderBy('fecha_pedido', 'desc');



        if ($tab === 'hoy') {
            $query->whereDate('fecha_pedido', now()->setTimezone('America/Tegucigalpa')->format('Y-m-d'));
        } elseif ($tab === 'futuro') {
            $query->whereDate('fecha_pedido', '>', now()->setTimezone('America/Tegucigalpa')->format('Y-m-d'));
        } elseif ($tab === 'pasado') {
            $query->whereDate('fecha_pedido', '<', now()->setTimezone('America/Tegucigalpa')->format('Y-m-d'));
        }


        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->whereHas('cliente', function ($q2) use ($buscar) {
                    $q2->where('nombre', 'like', '%' . $buscar . '%');
                })
                    ->orWhere('estado', 'like', '%' . $buscar . '%')
                    ->orWhereDate('fecha_pedido', $buscar)
                    ->orWhere('total', 'like', '%' . $buscar . '%');
            });
        }

        $pedidos = $query->paginate(10)->withQueryString(); // Mantener query params en paginación



        $pedidoSeleccionado = null;
        if ($request->has('pedido_id')) {
            $pedidoSeleccionado = Pedido::with(['cliente', 'detalles.platillo'])->find($request->pedido_id);
        }

        $estados = ['pendiente', 'en preparación', 'despachado', 'entregado', 'cancelado'];

        return view('admin.pedidos', compact('pedidos', 'pedidoSeleccionado', 'estados', 'tab'));
    }

    public function pedidosStatusViewCocina(Request $request)
    {
        $tab = $request->query('tab', 'hoy');
        $timezone = 'America/Tegucigalpa';
        $today = now()->setTimezone($timezone)->format('Y-m-d');

        $query = Pedido::with(['cliente', 'detalles.platillo'])
            ->orderBy('fecha_pedido', 'desc');

        // Filtros por pestaña
        switch ($tab) {
            case 'hoy':
                $query->whereDate('fecha_pedido', $today)
                    ->where('estado', 'en preparacion');
                break;
            case 'futuro':
                $query->whereDate('fecha_pedido', '>', $today);
                break;
            case 'pasado':
                $query->whereDate('fecha_pedido', '<', $today);
                break;
        }

        // Búsqueda incluyendo notas
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->whereHas('cliente', function ($q2) use ($buscar) {
                    $q2->where('nombre', 'like', '%' . $buscar . '%')
                        ->orWhere('telefono', 'like', '%' . $buscar . '%');
                })
                    ->orWhere('estado', 'like', '%' . $buscar . '%')
                    ->orWhereDate('fecha_pedido', $buscar)
                    ->orWhere('total', 'like', '%' . $buscar . '%')
                    ->orWhere('id', $buscar)
                    ->orWhere('notas', 'like', '%' . $buscar . '%'); // Nueva búsqueda en notas
            });
        }

        $pedidos = $query->paginate(10)->withQueryString();

        $pedidoSeleccionado = $request->has('pedido_id')
            ? Pedido::with(['cliente', 'detalles.platillo'])->find($request->pedido_id)
            : null;

        $estados = ['pendiente', 'en preparación', 'despachado', 'entregado', 'cancelado'];

        return view('cocina.pedidos-cocina', compact('pedidos', 'pedidoSeleccionado', 'estados', 'tab'));
    }

    public function actualizarEstadoCocina(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $estados = ['pendiente', 'en preparación', 'despachado', 'entregado', 'cancelado'];

        $estadoActual = $pedido->estado;
        $nuevoEstado = $request->input('nuevo_estado');

        $posActual = array_search($estadoActual, $estados);
        $posNuevo = array_search($nuevoEstado, $estados);

        // Validación: solo permitir avanzar
        if ($posNuevo === false || $posNuevo < $posActual) {
            return redirect()->back()->with('error', 'No se puede retroceder el estado del pedido.');
        }

        $pedido->estado = $nuevoEstado;
        $pedido->save();

        return redirect()->route('cocina.pedidosCocina')->with('success', 'Estado actualizado correctamente.');
    }
    public function actualizarEstado(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $estados = ['pendiente', 'en preparación', 'despachado', 'entregado', 'cancelado'];

        $estadoActual = $pedido->estado;
        $nuevoEstado = $request->input('nuevo_estado');

        $posActual = array_search($estadoActual, $estados);
        $posNuevo = array_search($nuevoEstado, $estados);

        // Validación: solo permitir avanzar
        if ($posNuevo === false || $posNuevo < $posActual) {
            return redirect()->back()->with('error', 'No se puede retroceder el estado del pedido.');
        }

        $pedido->estado = $nuevoEstado;
        $pedido->save();

        return redirect()->back()->with('success', 'Estado del pedido actualizado correctamente.');
    }

    /////////////////////////////Pedidos a futuro///////////////////////

    public function pedidosProgramadosView(Request $request)
    {
        return view('admin.pedidosProgramados');
    }

    public function obtenerPedidosPorFecha(Request $request)
    {
        $fecha = $request->query('fecha');

        if (!$fecha) {
            return response()->json(['error' => 'Fecha requerida'], 400);
        }

        $pedidos = Pedido::with('cliente')
            ->whereDate('fecha_pedido', $fecha)
            ->where('estado', '!=', 'cancelado')
            ->get()
            ->sortBy(function ($pedido) {
                return $pedido->cliente->nombre ?? '';
            })
            ->values(); // Reindexar para que no haya huecos

        return response()->json(['pedidos' => $pedidos]);

    }

    public function storePedidoProgramado(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'telefono' => 'required|string',
            'fecha' => 'required|date|after_or_equal:today',
            'mapa_url' => 'required|url',
            'platillos' => 'required|array|min:1',
            'platillos.*.id' => 'required|integer|exists:platillos,id',
            'platillos.*.cantidad' => 'required|integer|min:1',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
        ]);

        $fecha = $request->input('fecha');
        $mapaUrl = $request->input('mapa_url');

        // Extraer lat/lng desde el enlace
        if (!preg_match('/@([-0-9.]+),([-0-9.]+),/', $mapaUrl, $matches)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error: No se pudo extraer latitud y longitud del enlace de mapa.',
            ], 400);
        }

        $latitud = $matches[1];
        $longitud = $matches[2];

        $platillos = $request->input('platillos');
        $nombre = $request->input('nombre');
        $telefono = $request->input('telefono');
        $pago = $request->input('metodo_pago');

        $subtotal = 0.00;

        $distancia_km = 0;
        $tiempo_min = 0;

        $request = new Request([
            'target_lat' => (float) $latitud,
            'target_lng' => (float) $longitud
        ]);

        $response = app(VroomController::class)->calculateDistanceFromVehicle($request);
        $datosDistancia = $response->getData(true);



        // Fallback por si algo falla
        if (!isset($datosDistancia['success']) || !$datosDistancia['success']) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al calcular la distancia y el tiempo estimado',
                'total_final' => 0.00
            ], 400);
        }

        $datos = $datosDistancia['data'] ?? [];

        $distancia_km = $datos['route_info']['distance']['km'] ?? 0;
        $tiempo_min = $datos['route_info']['adjusted_delivery_time']['adjusted_time']['minutes'] ?? 0;

        // Coordenadas del restaurante
        $lat_restaurante = 14.107193046832785;
        $lon_restaurante = -87.1824026712528;

        // Calcular distancia con método interno (como respaldo y lógica extra)
        $distanciaManual = $this->calcularDistancia($lat_restaurante, $lon_restaurante, $latitud, $longitud);
        Log::info("Distancia manual calculada: {$distanciaManual} km");

        $cantidadPlatillos = 0;

        // Obtener día de la semana (0=domingo, 6=sábado)
        $diaSemana = now()->setTimezone('America/Tegucigalpa')->dayOfWeek;
        Log::info("Día de la semana: {$diaSemana}");

        foreach ($platillos as $i) {

            $cantidadPlatillos += $i['cantidad'];

        }

        $fechaCarbon = Carbon::parse($fecha);
        $aplicaEnvioGratis = EnvioGratisFecha::tieneEnvioGratisParaFecha($fechaCarbon);


        Log::info("estado del envio {$aplicaEnvioGratis} y cantidad de platillos pedidos {$cantidadPlatillos}");

        if ($aplicaEnvioGratis && $cantidadPlatillos >= $aplicaEnvioGratis->cantidad_minima) {
            $costo_envio = 0;
        } else {
            // 2. Si está muy cerca, también es gratis
            if ($distancia_km <= 0.7) {
                $costo_envio = 0;
            }
            // 3. Rangos definidos
            elseif ($distancia_km > 0.7 && $distancia_km <= 6.0) {
                $costo_envio = 40;
            } elseif ($distancia_km > 6.0 && $distancia_km <= 6.75) {
                $costo_envio = 50;
            } elseif ($distancia_km > 6.75 && $distancia_km <= 9.0) {
                $costo_envio = 70;
            } else { // mayor a 9.0
                $costo_envio = 80;
            }
        }



        try {
            $result = DB::transaction(function () use ($nombre, $telefono, $latitud, $longitud, $platillos, $fecha, $costo_envio, $pago, &$subtotal) {
                $cliente = Cliente::firstOrCreate(
                    ['telefono' => $telefono],
                    ['nombre' => $nombre]
                );

                $pedido = Pedido::create([
                    'cliente_id' => $cliente->id,
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'fecha_pedido' => $fecha . ' 12:00:00',
                    'total' => 0.00
                ]);

                foreach ($platillos as $item) {
                    $platilloId = $item['id'];
                    $cantidad = $item['cantidad'];

                    $menuItem = MenuDiario::where('fecha', $fecha)
                        ->where('platillo_id', $platilloId)
                        ->first();

                    if (!$menuItem) {
                        throw new \Exception("El platillo ID {$platilloId} no está en el menú del {$fecha}.");
                    }

                    if ($cantidad > $menuItem->cantidad_disponible) {
                        throw new \Exception("No hay suficientes unidades disponibles del platillo ID {$platilloId}.");
                    }

                    $platillo = Platillo::findOrFail($platilloId);

                    DetallePedido::create([
                        'pedido_id' => $pedido->id,
                        'platillo_id' => $platilloId,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $platillo->precio_base,
                    ]);

                    $menuItem->cantidad_disponible -= $cantidad;
                    $menuItem->save();

                    $subtotal += $cantidad * $platillo->precio_base;
                }

                $pedido->total = $subtotal + $costo_envio;
                $pedido->save();

                Pago::create([
                    'pedido_id' => $pedido->id,
                    'metodo_pago' => $pago,
                    'estado' => 'pendiente', // o el estado que corresponda por defecto
                ]);


                return [
                    'mensaje' => 'Pedido programado exitosamente.',
                    'total_final' => $subtotal + $costo_envio
                ];
            });

            return response()->json([
                'success' => true,
                'mensaje' => $result['mensaje'],
                'total' => $result['total_final']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => $e->getMessage()
            ], 400);
        }
    }


    public function editPedidoProgramado(Pedido $pedido)
    {
        $pedido->load(['cliente', 'detalles.platillo', 'pago']);

        // Obtener los platillos del menú del día para la fecha del pedido
        $fecha = Carbon::parse($pedido->fecha_pedido);
        $menu = MenuDiario::with('platillo')
            ->whereDate('fecha', $fecha)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->platillo->id,
                    'nombre' => $item->platillo->nombre,
                    'precio' => $item->platillo->precio_base,
                    'cantidad_disponible' => $item->cantidad_disponible,
                ];
            })
            ->unique('id')
            ->values();


        $urlMaps = $pedido->latitud && $pedido->longitud
            ? "https://www.google.com/maps/@{$pedido->latitud},{$pedido->longitud},15z"
            : null;


        return response()->json([
            'cliente' => [
                'nombre' => $pedido->cliente->nombre,
                'telefono' => $pedido->cliente->telefono,
            ],
            'latitud' => $pedido->latitud,
            'longitud' => $pedido->longitud,
            'url_maps' => $urlMaps,
            'fecha_pedido' => Carbon::parse($pedido->fecha_pedido)->toDateString(),
            'platillos' => $pedido->detalles->map(function ($detalle) {
                return [
                    'platillo_id' => $detalle->platillo_id,
                    'nombre' => $detalle->platillo->nombre,
                    'cantidad' => $detalle->cantidad,
                    'precio' => $detalle->precio_unitario,
                ];
            }),
            'metodo_pago' => optional($pedido->pago)->metodo_pago,
            'menu_dia' => $menu,
        ]);
    }


    public function actualizarDatosPago(Request $request, $pedidoId)
    {
        $request->validate([
            'request_id' => 'required|string',
            'referencia_transaccion' => 'required|string',
        ]);


        $pago = Pago::where('pedido_id', $pedidoId)->first();

        if (!$pago) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Pago no encontrado para este pedido.',
            ], 404);
        }

        // Actualizar los datos del pago
        $pago->request_id = $request->input('request_id');
        $pago->referencia_transaccion = $request->input('referencia_transaccion');
        $pago->save();

        return response()->json([
            'success' => true,
            'mensaje' => 'Datos de pago actualizados correctamente.',
            'pago' => $pago,
        ]);
    }

    public function updatePedidoProgramado(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'mapa_url' => 'required|url',
            'platillos' => 'required|array|min:1',
            'platillos.*.platillo_id' => 'required|integer|exists:platillos,id',
            'platillos.*.cantidad' => 'required|integer|min:0',
            'platillos.*.precio' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
        ]);


        $cliente = Cliente::firstOrCreate(
            [
                'nombre' => $request->nombre,
                'telefono' => $request->telefono,
            ],
        );

        $pedido = Pedido::with(['detalles', 'pago'])->findOrFail($id);
        $fecha = Carbon::parse($pedido->fecha_pedido)->format('Y-m-d'); // ✅ convierte string a Carbon


        // Extraer coordenadas del mapa
        if (!preg_match('/@([-0-9.]+),([-0-9.]+),/', $request->mapa_url, $matches)) {
            return response()->json(['success' => false, 'mensaje' => 'No se pudo extraer latitud/longitud.'], 400);
        }
        $latitud = $matches[1];
        $longitud = $matches[2];

        $platillosInput = collect($request->platillos)->keyBy('platillo_id');
        $platillosActuales = $pedido->detalles->keyBy('platillo_id');

        $platillos = $request->platillos;

        $subtotal = 0.0;

        $distancia_km = 0;
        $tiempo_min = 0;

        $request2 = new Request([
            'target_lat' => (float) $latitud,
            'target_lng' => (float) $longitud
        ]);

        $response = app(VroomController::class)->calculateDistanceFromVehicle($request2);
        $datosDistancia = $response->getData(true);



        // Fallback por si algo falla
        if (!isset($datosDistancia['success']) || !$datosDistancia['success']) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al calcular la distancia y el tiempo estimado',
                'total_final' => 0.00
            ], 400);
        }

        $datos = $datosDistancia['data'] ?? [];

        $distancia_km = $datos['route_info']['distance']['km'] ?? 0;
        $tiempo_min = $datos['route_info']['adjusted_delivery_time']['adjusted_time']['minutes'] ?? 0;

        // Coordenadas del restaurante
        $lat_restaurante = 14.107193046832785;
        $lon_restaurante = -87.1824026712528;

        // Calcular distancia con método interno (como respaldo y lógica extra)
        $distanciaManual = $this->calcularDistancia($lat_restaurante, $lon_restaurante, $latitud, $longitud);
        Log::info("Distancia manual calculada: {$distanciaManual} km");


        // Obtener día de la semana (0=domingo, 6=sábado)
        $diaSemana = now()->setTimezone('America/Tegucigalpa')->dayOfWeek;
        Log::info("Día de la semana: {$diaSemana}");

        $cantidadPlatillos = 0;

        foreach ($platillos as $i) {

            $cantidadPlatillos += $i['cantidad'];

        }

        $fechaCarbon = Carbon::parse($fecha);
        $aplicaEnvioGratis = EnvioGratisFecha::tieneEnvioGratisParaFecha($fechaCarbon);
        if ($aplicaEnvioGratis && $cantidadPlatillos >= $aplicaEnvioGratis->cantidad_minima) {
            $costo_envio = 0;
        } else {
            // 2. Si está muy cerca, también es gratis
            if ($distancia_km <= 0.7) {
                $costo_envio = 0;
            }
            // 3. Rangos definidos
            elseif ($distancia_km > 0.7 && $distancia_km <= 6.0) {
                $costo_envio = 40;
            } elseif ($distancia_km > 6.0 && $distancia_km <= 6.75) {
                $costo_envio = 50;
            } elseif ($distancia_km > 6.75 && $distancia_km <= 9.0) {
                $costo_envio = 70;
            } else { // mayor a 9.0
                $costo_envio = 80;
            }
        }


        try {
            DB::transaction(function () use ($pedido, $platillosInput, $platillosActuales, $costo_envio, $fecha, $latitud, $longitud, $cliente, $request, &$subtotal) {

                // 1. Revertir stock de platillos eliminados o disminuidos
                foreach ($platillosActuales as $platilloId => $detalle) {
                    $cantidadNueva = $platillosInput[$platilloId]['cantidad'] ?? 0;
                    $diferencia = $detalle->cantidad - $cantidadNueva;

                    if ($diferencia > 0) {
                        $menuItem = MenuDiario::where('fecha', $fecha)
                            ->where('platillo_id', $platilloId)
                            ->first();

                        if ($menuItem) {
                            $menuItem->cantidad_disponible += $diferencia;
                            $menuItem->save();
                        }

                        if ($cantidadNueva == 0) {
                            $detalle->delete(); // Eliminar detalle si la cantidad se puso en cero
                        } else {
                            $detalle->cantidad = $cantidadNueva;
                            $detalle->save();
                        }
                    }
                }

                // 2. Agregar o aumentar platillos nuevos
                foreach ($platillosInput as $platilloId => $datos) {
                    $cantidad = $datos['cantidad'];
                    $precio = $datos['precio'];

                    if ($cantidad <= 0)
                        continue; // Ya fue procesado arriba

                    $menuItem = MenuDiario::where('fecha', $fecha)
                        ->where('platillo_id', $platilloId)
                        ->first();

                    if (!$menuItem) {
                        throw new \Exception("El platillo ID {$platilloId} no está en el menú del {$fecha}.");
                    }

                    $yaExistia = $platillosActuales->has($platilloId);
                    $cantidadActual = $yaExistia ? $platillosActuales[$platilloId]->cantidad : 0;
                    $incremento = $cantidad - $cantidadActual;

                    if ($incremento > 0) {
                        if ($incremento > $menuItem->cantidad_disponible) {
                            throw new \Exception("No hay suficientes unidades disponibles del platillo ID {$platilloId}.");
                        }

                        $menuItem->cantidad_disponible -= $incremento;
                        $menuItem->save();
                    }

                    if ($yaExistia) {
                        $detalle = $platillosActuales[$platilloId];
                        $detalle->cantidad = $cantidad;
                        $detalle->precio_unitario = $precio;
                        $detalle->save();
                    } else {
                        DetallePedido::create([
                            'pedido_id' => $pedido->id,
                            'platillo_id' => $platilloId,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precio,
                        ]);
                    }

                    $subtotal += $cantidad * $precio;
                }

                // 3. Actualizar coordenadas y total
                $pedido->latitud = $latitud;
                $pedido->longitud = $longitud;
                $pedido->total = $subtotal + $costo_envio;
                $pedido->cliente_id = $cliente->id;
                $pedido->save();

                // 4. Actualizar método de pago
                if ($pedido->pago) {
                    $pedido->pago->metodo_pago = $request->metodo_pago;
                    $pedido->pago->save();
                } else {
                    Pago::create([
                        'pedido_id' => $pedido->id,
                        'metodo_pago' => $request->metodo_pago,
                        'estado' => 'pendiente',
                    ]);
                }
            });

            return response()->json(['success' => true, 'mensaje' => 'Pedido actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 400);
        }
    }

    public function cancelarPedidoProgramado($id)
    {
        try {
            $pedido = Pedido::with(['detalles', 'pago'])->findOrFail($id);

            if ($pedido->estado === 'cancelado') {
                return response()->json(['success' => false, 'mensaje' => 'Este pedido ya fue cancelado.'], 400);
            }

            $fecha = Carbon::parse($pedido->fecha_pedido)->format('Y-m-d');

            DB::transaction(function () use ($pedido, $fecha) {
                foreach ($pedido->detalles as $detalle) {
                    $menuItem = MenuDiario::where('fecha', $fecha)
                        ->where('platillo_id', $detalle->platillo_id)
                        ->first();

                    if ($menuItem) {
                        $menuItem->cantidad_disponible += $detalle->cantidad;
                        $menuItem->save();
                    }
                }

                // Marcar el pedido como cancelado
                $pedido->estado = 'cancelado';
                $pedido->save();
            });

            return response()->json(['success' => true, 'mensaje' => 'Pedido cancelado y stock restaurado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 400);
        }
    }


    public function cancelarPedidoBot($id)
    {
        try {
            $pedido = Pedido::with(['detalles', 'pago'])->findOrFail($id);

            // Validar que el pedido esté en estado "pendiente"
            if ($pedido->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Solo se pueden cancelar pedidos en estado pendiente.'
                ], 400);
            }

            // Validar que la fecha del pedido sea hoy
            $fechaPedido = Carbon::parse($pedido->fecha_pedido)->toDateString();
            $hoy = Carbon::now('America/Tegucigalpa')->toDateString();

            if ($fechaPedido !== $hoy) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Solo se pueden cancelar pedidos programados para hoy.'
                ], 400);
            }

            // Transacción para cancelar el pedido y restaurar el stock
            DB::transaction(function () use ($pedido, $fechaPedido) {
                foreach ($pedido->detalles as $detalle) {
                    $menuItem = MenuDiario::where('fecha', $fechaPedido)
                        ->where('platillo_id', $detalle->platillo_id)
                        ->first();

                    if ($menuItem) {
                        $menuItem->cantidad_disponible += $detalle->cantidad;
                        $menuItem->save();
                    }
                }

                // Marcar el pedido como cancelado
                $pedido->estado = 'cancelado';
                $pedido->save();
            });

            return response()->json([
                'success' => true,
                'mensaje' => 'Pedido cancelado y stock restaurado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al cancelar el pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function verificarPagoPendiente(Request $request)
    {
        $telefono = $request->input('telefono');

        // Buscar cliente por teléfono
        $cliente = Cliente::where('telefono', $telefono)->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        // Fecha de hoy (sin hora)
        $hoy = Carbon::today();

        // Buscar pedidos del cliente con pagos pendientes hoy
        $pedidoPendiente = $cliente->pedidos()
            ->whereDate('fecha_pedido', $hoy)
            ->whereHas('pago', function ($query) {
                $query->where('estado_pago', 'pendiente');
            })
            ->first();

        if ($pedidoPendiente) {
            return response()->json([
                'success' => true,
                'message' => 'El cliente tiene un pedido con pago pendiente para hoy',
                'cliente' => $cliente->nombre,
                'pedido_id' => $pedidoPendiente->id,
                'total' => $pedidoPendiente->total,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'El cliente no tiene pagos pendientes para hoy',
            'cliente' => $cliente->nombre,
        ]);
    }

}



