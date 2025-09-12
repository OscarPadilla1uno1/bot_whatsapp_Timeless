<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hora extends Model
{
    use HasFactory;

    protected $table = 'hora';

    protected $fillable = [
        'id', // Añadir id a los fillable para permitir la actualización
        'hora_inicio',
        'hora_fin',
        'activo',
        'dias_semana'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i'
    ];

    /**
     * Obtener los días de la semana como array
     */
    public function getDiasSemanaArrayAttribute()
    {
        return explode(',', $this->dias_semana);
    }

    /**
     * Verificar si el bot está activo en el momento actual
     */
    public static function estaActivo()
    {
        $configuracion = self::firstOrCreate(
            ['id' => 1],
            [
                'hora_inicio' => '08:00:00',
                'hora_fin' => '22:00:00',
                'activo' => true,
                'dias_semana' => '1,2,3,4,5,6,7'
            ]
        );
        
        if (!$configuracion->activo) {
            return false;
        }

        // Verificar día de la semana
        $diaActual = now()->dayOfWeekIso; // 1 (Lunes) a 7 (Domingo)
        $diasActivos = $configuracion->getDiasSemanaArrayAttribute();
        
        if (!in_array($diaActual, $diasActivos)) {
            return false;
        }

        // Verificar horario
        $horaActual = now()->format('H:i:s');
        $horaInicio = $configuracion->hora_inicio->format('H:i:s');
        $horaFin = $configuracion->hora_fin->format('H:i:s');
        
        return $horaActual >= $horaInicio && $horaActual <= $horaFin;
    }
}