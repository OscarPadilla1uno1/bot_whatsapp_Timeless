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
use Illuminate\Support\Facades\Log; // 👈 Importación requerida
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class AdminController extends Controller
{
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

}
