<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedido';
    protected $fillable = ['pedido_id', 'platillo_id', 'cantidad', 'precio_unitario'];
    
    // Si tu tabla no tiene timestamps
    public $timestamps = false;
    
    // Relación con pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
    
    // Relación con platillo
    public function platillo()
    {
        return $this->belongsTo(Platillo::class);
    }
}