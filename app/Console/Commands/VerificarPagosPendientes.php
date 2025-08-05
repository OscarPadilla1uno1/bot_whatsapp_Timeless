<?php

namespace App\Console\Commands;

use App\Models\Pago;
use Dnetix\Redirection\PlacetoPay;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

class VerificarPagosPendientes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagos:verificar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('⏱️ Cronjob iniciado: Verificando pagos pendientes');

        $pagosPendientes = Pago::where('estado_pago', 'pendiente')
            ->where('metodo_pago', 'tarjeta')
            ->whereNotNull('request_id')
            ->get();

        if ($pagosPendientes->isEmpty()) {
            Log::info('✅ No hay pagos pendientes por verificar.');
            return;
        }

        $placetopay = new PlacetoPay([
            'login' => env('PLACETOPAY_LOGIN'),
            'tranKey' => env('SecretKey'),
            'baseUrl' => env('PLACETOPAY_BASE_URL'),
            'timeout' => env('PLACETOPAY_TIMEOUT', 10),
        ]);

        foreach ($pagosPendientes as $pago) {
            try {
                $response = $placetopay->query($pago->request_id);

                if (!$response->isSuccessful()) {
                    Log::warning("⚠️ Error al consultar request_id {$pago->request_id}: " . $response->status()->message());
                    continue;
                }

                $estado = $response->status()->status();

                if ($response->status()->isApproved()) {
                    $pago->estado_pago = 'confirmado';
                    Log::info("✅ Pago aprobado: Pedido ID {$pago->pedido_id}");
                } elseif ($estado === 'REJECTED' || $estado === 'FAILED') {
                    $pago->estado_pago = 'fallido';
                    Log::info("❌ Pago rechazado o fallido: Pedido ID {$pago->pedido_id}");
                } else {
                    Log::info("⏳ Pago aún pendiente: Pedido ID {$pago->pedido_id}");
                }

                $pago->save();
            } catch (\Exception $e) {
                Log::error("🚨 Excepción al procesar pago {$pago->pedido_id}: " . $e->getMessage());
            }
        }

        Log::info('🏁 Cronjob finalizado.');
    }

}
