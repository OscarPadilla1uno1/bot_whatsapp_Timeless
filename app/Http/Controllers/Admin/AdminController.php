<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Platillo;

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

        DB::statement('CALL actualizar_cantidad_menu(?, ?, ?)', [
            $request->platillo_id,
            $request->cantidad,
            $request->fecha
        ]);

        return response()->json(['success' => true]);
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
        ]);
    
        DB::statement('CALL InsertarPlatillo(?, ?, ?)', [
            $request->nombre,
            $request->descripcion,
            $request->precio_base
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
        ]);

        DB::statement('CALL sp_actualizar_platillo(?, ?, ?, ?)', [
            $request->id,
            $request->nombre,
            $request->descripcion,
            $request->precio_base,
        ]);

        return response()->json(['success' => true]);
    }

    // Eliminar platillo
    public function eliminarPlatilloCatalogo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:platillos,id'
        ]);

        DB::statement('CALL sp_borrar_platillo(?)', [$request->id]);

        return response()->json(['success' => true]);
    }





}
