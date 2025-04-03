<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    //
    public function index()
    {
        // Llamamos al procedimiento almacenado para obtener los platillos actuales
        $platillosEnMenu = DB::select('CALL obtener_menu_diario()');

        // Llamamos a otro procedimiento o consulta para obtener todos los platillos disponibles
        $todosLosPlatillos = DB::select('CALL ObtenerTodosLosPlatillos()');

        // Retornamos la vista pasando los datos
        return view('admin.menu-diario', compact('platillosEnMenu', 'todosLosPlatillos'));
    }

    public function agregarPlatillo(Request $request)
    {
        // Validamos los datos
        $request->validate([
            'platillo_id' => 'required|exists:platillos,id',
            'cantidad' => 'required|integer|min:1'
        ]);

        // Ejecutamos el procedimiento almacenado para agregar o actualizar el menú
        DB::statement('CALL actualizar_menu_diario(?, ?)', [
            $request->platillo_id,
            $request->cantidad
        ]);

        // Redirigimos de vuelta con un mensaje de éxito
        return redirect()->route('admin.menu.index')->with('success', 'Platillo agregado al menú diario correctamente.');
    }
}
