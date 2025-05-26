<?php
// app/Models/RouteAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RouteAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'route_data',
        'route_hash',
        'calculated_at'
    ];

    protected $casts = [
        'route_data' => 'array', // Cambiar de 'json' a 'array' para mejor manejo
        'calculated_at' => 'datetime'
    ];

    // Definir la tabla explícitamente si es necesario
    protected $table = 'route_assignments';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Método mejorado para establecer route_data
     */
    public function setRouteDataAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['route_data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($value)) {
            // Verificar si ya es JSON válido
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->attributes['route_data'] = $value;
            } else {
                throw new \InvalidArgumentException('route_data debe ser un array o JSON válido');
            }
        } else {
            throw new \InvalidArgumentException('route_data debe ser un array o string JSON');
        }
    }
    
    /**
     * Método mejorado para obtener route_data
     */
    public function getRouteDataAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            // Si no se puede decodificar, devolver array vacío
            return [];
        }
        
        return is_array($value) ? $value : [];
    }

    /**
     * Scope para obtener asignaciones por hash
     */
    public function scopeByHash($query, $hash)
    {
        return $query->where('route_hash', $hash);
    }

    /**
     * Scope para obtener asignaciones recientes
     */
    public function scopeRecent($query, $hours = 1)
    {
        return $query->where('calculated_at', '>=', now()->subHours($hours));
    }

    /**
     * Método para validar la integridad de los datos
     */
    public function validateData()
    {
        $errors = [];

        if (empty($this->user_id)) {
            $errors[] = 'user_id es requerido';
        }

        if (empty($this->route_data)) {
            $errors[] = 'route_data es requerido';
        }

        if (empty($this->route_hash)) {
            $errors[] = 'route_hash es requerido';
        }

        if (!$this->calculated_at) {
            $errors[] = 'calculated_at es requerido';
        }

        return empty($errors) ? true : $errors;
    }
}