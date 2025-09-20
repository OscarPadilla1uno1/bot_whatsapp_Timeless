<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id',
        'metodo_pago',
        'estado_pago',
        'fecha_pago',
        'referencia_transaccion',
        'request_id',
        'process_url',
        'metodo_interno',
        'canal',
        'observaciones',
        'internal_reference',
        'notificado',
    ];

    // Si tu tabla no tiene timestamps
    public $timestamps = false;

    // Relación con pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
