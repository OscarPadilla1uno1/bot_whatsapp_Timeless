<?php

namespace App\Http\Controllers;

use App\Models\Hora;
use Illuminate\Http\Request;

class HoraController extends Controller
{
    /**
     * Mostrar el formulario de configuración
     */
    public function index()
    {
        // Obtener o crear la configuración con ID 1
        $configuracion = Hora::firstOrCreate(
            ['id' => 1],
            [
                'hora_inicio' => '08:00:00',
                'hora_fin' => '22:00:00',
                'activo' => true,
                'dias_semana' => '1,2,3,4,5,6,7'
            ]
        );
        
        $diasSemana = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];
        
        return view('bot.hora', compact('configuracion', 'diasSemana'));
    }

    /**
     * Actualizar la configuración
     */
    public function update(Request $request)
    {
        $request->validate([
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'dias_semana' => 'required|array',
            'dias_semana.*' => 'integer|between:1,7',
            'activo' => 'boolean'
        ]);

        // Buscar la configuración con ID 1 o crearla si no existe
        $configuracion = Hora::firstOrNew(['id' => 1]);
        
        $configuracion->hora_inicio = $request->hora_inicio . ':00';
        $configuracion->hora_fin = $request->hora_fin . ':00';
        $configuracion->dias_semana = implode(',', $request->dias_semana);
        $configuracion->activo = $request->has('activo');
        $configuracion->save();

        return redirect()->back()->with('success', 'Configuración actualizada correctamente.');
    }

    /**
     * API: Obtener la configuración actual
     */
    public function getConfiguracion()
    {
        $configuracion = Hora::firstOrCreate(
            ['id' => 1],
            [
                'hora_inicio' => '08:00:00',
                'hora_fin' => '22:00:00',
                'activo' => true,
                'dias_semana' => '1,2,3,4,5,6,7'
            ]
        );

        return response()->json([
            'hora_inicio' => $configuracion->hora_inicio->format('H:i'),
            'hora_fin' => $configuracion->hora_fin->format('H:i'),
            'activo' => (bool) $configuracion->activo,
            'dias_semana' => $configuracion->getDiasSemanaArrayAttribute(),
            'esta_activo' => Hora::estaActivo()
        ]);
    }

    /**
     * API: Verificar si el bot está activo en este momento
     */
    public function verificarActivo()
    {
        return response()->json([
            'activo' => Hora::estaActivo()
        ]);
    }
}