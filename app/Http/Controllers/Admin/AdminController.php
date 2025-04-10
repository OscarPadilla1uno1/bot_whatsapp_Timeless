<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Platillo;
use Illuminate\Support\Facades\Storage;


class AdminController extends Controller
{
    //
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

    public function storePedido($nombre, $telefono, $direccion, array $platillos)
{
    // Crea la tabla temporal
    DB::statement("CREATE TEMPORARY TABLE temp_pedido_detalle (platillo_id INT, cantidad INT)");

    // Llena la tabla temporal con los platillos
    foreach ($platillos as $platillo) {
        DB::insert("INSERT INTO temp_pedido_detalle (platillo_id, cantidad) VALUES (?, ?)", [
            $platillo['id'],
            $platillo['cantidad']
        ]);
    }

    // Llama al procedimiento almacenado
    DB::statement("CALL PEDIDO_CONFIRMADO(?, ?, ?, @mensaje, @total)", [
        $nombre,
        $telefono,
        $direccion
    ]);

    // Recupera los resultados del procedimiento almacenado
    $res = DB::select("SELECT @mensaje AS mensaje, @total AS total")[0];

    // Verifica si hay mensaje de error
    if (strpos($res->mensaje, 'Error:') === 0) {
        return response()->json([
            'success' => false,
            'mensaje' => $res->mensaje
        ], 400);
    }

    // Retorna la respuesta exitosa
    return response()->json([
        'success' => true,
        'mensaje' => $res->mensaje,
        'total' => $res->total
    ], 200);
}





}
