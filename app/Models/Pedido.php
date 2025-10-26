<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $fillable = ['cliente_id', 'latitud', 'longitud', 'domicilio', 'estado', 'total', 'fecha_pedido', 'notas'];
    
    public $timestamps = false;

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

    public function pagosConsolidados()
{
    return $this->belongsToMany(
        PagoConsolidado::class,
        'pago_consolidado_pedidos',
        'pedido_id',
        'pago_consolidado_id'
    )->withPivot('pagado')
     ->withTimestamps();
}

}
