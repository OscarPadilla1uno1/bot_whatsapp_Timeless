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
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Barryvdh\DomPDF\Facade\Pdf;



class AdminController extends Controller
{

    public function descargarFactura($id)
    {
        $pedido = Pedido::with(['cliente', 'detalles.platillo'])->findOrFail($id);

        $pdf = PDF::loadView('pdf.factura', compact('pedido'));
        return $pdf->download("factura_pedido_{$pedido->id}.pdf");
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
        ]);

        $nombre = $request->nombre;
        $telefono = $request->telefono;
        $latitud = $request->latitud;
        $longitud = $request->longitud;
        $platillos = $request->platillos;

        $hoy = now()->setTimezone('America/Tegucigalpa')->format('Y-m-d'); // Ajusta tu zona horaria

        Log::info("Fecha generada: " . $hoy);
        $subtotal = 0.00;

        // Verificar que hay platillos
        if (empty($platillos)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error: No hay platillos en el pedido',
                'total_final' => 0.00
            ], 400);
        }

        try {
            $result = DB::transaction(function () use ($nombre, $telefono, $latitud, $longitud, $platillos, $hoy, &$subtotal) {
                // Verificar si el cliente ya existe o crearlo
                $cliente = Cliente::firstOrCreate(
                    ['telefono' => $telefono],
                    ['nombre' => $nombre]
                );

                // Crear el pedido
                $pedido = new Pedido([
                    'cliente_id' => $cliente->id,
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'fecha_pedido' => $hoy,
                    'total' => 0.00 // Se actualizará al final
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
                $pedido->total = $subtotal;
                $pedido->save();

                return [
                    'mensaje' => 'Pedido registrado exitosamente.',
                    'total_final' => $subtotal
                ];
            });

            return response()->json([
                'success' => true,
                'mensaje' => $result['mensaje'],
                'total' => $result['total_final']
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => $e->getMessage(),
                'total_final' => 0.00
            ], 400);
        }
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
        $tab = $request->query('tab', 'hoy'); // Por defecto "hoy"

        $query = Pedido::with('cliente')->orderBy('fecha_pedido', 'desc');



        if ($tab === 'hoy') {
            $query->whereDate('fecha_pedido', now()->setTimezone('America/Tegucigalpa')->format('Y-m-d'))->where('estado', 'en preparacion'); // Solo pedidos en preparación
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

        try {
            $result = DB::transaction(function () use ($nombre, $telefono, $latitud, $longitud, $platillos, $fecha, $pago, &$subtotal) {
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

                $pedido->total = $subtotal;
                $pedido->save();

                Pago::create([
                    'pedido_id' => $pedido->id,
                    'metodo_pago' => $pago,
                    'estado' => 'pendiente', // o el estado que corresponda por defecto
                ]);


                return [
                    'mensaje' => 'Pedido programado exitosamente.',
                    'total_final' => $subtotal
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

        $subtotal = 0.0;

        try {
            DB::transaction(function () use ($pedido, $platillosInput, $platillosActuales, $fecha, $latitud, $longitud, $cliente, $request, &$subtotal) {

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
                $pedido->total = $subtotal;
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





}



