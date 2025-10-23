<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoConsolidado extends Model
{
    protected $table = 'pagos_consolidados';

    protected $casts = [
        'fecha_pago' => 'datetime',
    ];

    protected $fillable = [
        'cliente_id',
        'monto_total',
        'metodo_pago',
        'estado_pago',
        'referencia_transaccion',
        'request_id',
        'process_url',
        'fecha_pago',
        'canal',
        'observaciones',
        'notificado',
    ];

    public $timestamps = true;

    /* =============================
       🔗 RELACIONES
       ============================= */

    // Un pago consolidado pertenece a un cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Un pago consolidado puede tener muchos pedidos asociados
    public function pedidos()
    {
        return $this->belongsToMany(
            Pedido::class,
            'pago_consolidado_pedidos',   // tabla pivote
            'pago_consolidado_id',        // FK en pivote hacia este modelo
            'pedido_id'                   // FK en pivote hacia pedidos
        )->withPivot('pagado')            // campo adicional en la pivote
            ->withTimestamps();
    }

    /* =============================
       ⚙️ MÉTODOS DE UTILIDAD
       ============================= */

    // Determina si el pago está confirmado
    public function estaConfirmado(): bool
    {
        return $this->estado_pago === 'confirmado';
    }

    // Marca el pago como notificado (para evitar reenvíos al bot)
    public function marcarNotificado(): void
    {
        $this->notificado = true;
        $this->save();
    }

    // Retorna el total con formato
    public function getTotalFormateadoAttribute(): string
    {
        return number_format($this->monto_total, 2) . ' HNL';
    }
}
