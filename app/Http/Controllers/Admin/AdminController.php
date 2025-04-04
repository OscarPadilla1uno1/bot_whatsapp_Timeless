<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    //
    public function index(Request $request)
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




}
