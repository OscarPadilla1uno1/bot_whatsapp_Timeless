<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoConsolidadoPedido extends Model
{
    protected $table = 'pago_consolidado_pedidos';

    protected $fillable = [
        'pago_consolidado_id',
        'pedido_id',
        'pagado',
    ];

    public $timestamps = true;

    /* =============================
       🔗 RELACIONES
       ============================= */

    public function pagoConsolidado()
    {
        return $this->belongsTo(PagoConsolidado::class, 'pago_consolidado_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /* =============================
       ⚙️ MÉTODOS DE UTILIDAD
       ============================= */

    public function marcarPagado(): void
    {
        $this->pagado = true;
        $this->save();
    }

    public function marcarPendiente(): void
    {
        $this->pagado = false;
        $this->save();
    }
}
