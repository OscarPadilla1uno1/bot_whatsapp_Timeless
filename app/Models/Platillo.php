<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platillo extends Model
{
    use HasFactory;

    protected $table = 'platillos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_base',
        'imagen_url',
        'activo',
    ];

    public $timestamps = false; public function menuDiario()
    {
        return $this->hasMany(MenuDiario::class);
    }
    
    // Relación con detalles de pedido
    public function detallesPedido()
    {
        return $this->hasMany(DetallePedido::class);
    }
}
