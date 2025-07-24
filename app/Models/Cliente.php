<?php

// Si no tienes estos modelos, créalos con:
// php artisan make:model Cliente
// php artisan make:model Pedido  
// php artisan make:model DetallePedido
// php artisan make:model Platillo

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Modelo Cliente
class Cliente extends Model
{
        public $timestamps = false; // AGREGAR ESTA LÍNEA

    protected $fillable = [
        'nombre',
        'telefono'
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}
