<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $fillable = ['cliente_id', 'latitud', 'longitud', 'estado', 'total'];
    
    // Si tu tabla usa fecha_pedido en lugar de created_at y no tiene updated_at
    const CREATED_AT = 'fecha_pedido';
    const UPDATED_AT = null;
    
    // Relación con cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    
    // Relación con detalles de pedido
    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }
    
    // Relación con pago
    public function pago()
    {
        return $this->hasOne(Pago::class);
    }
}
