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
    protected $fillable = [
        'nombre',
        'telefono'
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}

// Modelo Pedido
class Pedido extends Model
{
    protected $fillable = [
        'cliente_id',
        'latitud',
        'longitud',
        'estado',
        'total'
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'total' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function pago()
    {
        return $this->hasOne(Pago::class);
    }

    // Scopes útiles
    public function scopeDespachados($query)
    {
        return $query->where('estado', 'despachado');
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_pedido', today());
    }
}

// Modelo DetallePedido
class DetallePedido extends Model
{
    protected $table = 'detalle_pedido';
    
    protected $fillable = [
        'pedido_id',
        'platillo_id',
        'cantidad',
        'precio_unitario'
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function platillo()
    {
        return $this->belongsTo(Platillo::class);
    }
}

// Modelo Platillo
class Platillo extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_base',
        'imagen_url',
        'activo'
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'activo' => 'boolean'
    ];

    public function detallesPedido()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function menuDiario()
    {
        return $this->hasMany(MenuDiario::class);
    }
}

// También asegúrate de que tu modelo User tenga los campos necesarios
