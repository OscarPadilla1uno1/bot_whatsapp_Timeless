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
    public $timestamps = false; // Si no usas created_at y updated_at
    protected $fillable = [
        'nombre',
        'telefono'
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function pagosConsolidados()
{
    return $this->hasMany(PagoConsolidado::class, 'cliente_id');
}
}
