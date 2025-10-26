<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Pago;
use App\Models\PagoConsolidado;
use App\Models\Platillo;
use Rap2hpoutre\FastExcel\FastExcel;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\PagoConsolidadoPedido;

class ExportController extends Controller
{
    /* ====================================================
       📊 EXPORTACIONES INDIVIDUALES
       ==================================================== */

    public function exportClientes()
    {
        $data = Cliente::all()->map(fn($c) => [
            'ID' => $c->id,
            'Nombre' => $c->nombre,
            'Teléfono' => $c->telefono,
        ]);

        return (new FastExcel($data))->download('clientes.xlsx');
    }

    public function exportPedidos()
    {
        $data = Pedido::with('cliente')->get()->map(fn($p) => [
            'ID Pedido' => $p->id,
            'Cliente' => $p->cliente->nombre ?? 'N/A',
            'Teléfono' => $p->cliente->telefono ?? 'N/A',
            'Total' => $p->total,
            'Estado' => $p->estado,
            'Domicilio' => $p->domicilio ? 'Sí' : 'No',
            'Fecha Pedido' => $p->fecha_pedido,
            'Notas' => $p->notas,
        ]);

        return (new FastExcel($data))->download('pedidos.xlsx');
    }

    public function exportPagoConsolidadoPedidos()
    {
        $data = PagoConsolidadoPedido::with(['pagoConsolidado.cliente', 'pedido.cliente'])
            ->get()
            ->map(function ($rel) {
                return [
                    'ID Relación' => $rel->id,
                    'ID Pago Consolidado' => $rel->pago_consolidado_id,
                    'Cliente del Pago' => $rel->pagoConsolidado?->cliente?->nombre ?? 'N/A',
                    'ID Pedido' => $rel->pedido_id,
                    'Cliente del Pedido' => $rel->pedido?->cliente?->nombre ?? 'N/A',
                    'Pagado' => $rel->pagado ? 'Sí' : 'No',
                    'Fecha Relación' => optional($rel->created_at)->format('Y-m-d H:i'),
                    'Última Actualización' => optional($rel->updated_at)->format('Y-m-d H:i'),
                ];
            });

        return (new FastExcel($data))->download('pago_consolidado_pedidos.xlsx');
    }

    public function exportPagos()
    {
        $data = Pago::with('pedido.cliente')->get()->map(fn($p) => [
            'ID Pago' => $p->id,
            'Cliente' => $p->pedido->cliente->nombre ?? 'N/A',
            'Pedido ID' => $p->pedido_id,
            'Método Pago' => $p->metodo_pago,
            'Estado Pago' => $p->estado_pago,
            'Fecha Pago' => $p->fecha_pago,
            'Canal' => $p->canal,
            'Referencia' => $p->referencia_transaccion,
            'Observaciones' => $p->observaciones,
        ]);

        return (new FastExcel($data))->download('pagos.xlsx');
    }

    public function exportPagosConsolidados()
    {
        $data = PagoConsolidado::with(['cliente', 'pedidos'])->get()->map(function ($p) {
            $pedidosIds = $p->pedidos->pluck('id')->implode(', ');
            return [
                'ID Pago Consolidado' => $p->id,
                'Cliente' => $p->cliente->nombre ?? 'N/A',
                'Teléfono' => $p->cliente->telefono ?? 'N/A',
                'Monto Total' => $p->monto_total,
                'Método Pago' => $p->metodo_pago,
                'Estado Pago' => $p->estado_pago,
                'Pedidos Asociados' => $pedidosIds,
                'Fecha Pago' => optional($p->fecha_pago)->format('Y-m-d H:i'),
                'Canal' => $p->canal,
                'Observaciones' => $p->observaciones,
            ];
        });

        return (new FastExcel($data))->download('pagos_consolidados.xlsx');
    }

    public function exportPlatillos()
    {
        $data = Platillo::all()->map(fn($p) => [
            'ID' => $p->id,
            'Nombre' => $p->nombre,
            'Descripción' => $p->descripcion,
            'Precio Base' => $p->precio_base,
            'Activo' => $p->activo ? 'Sí' : 'No',
        ]);

        return (new FastExcel($data))->download('platillos.xlsx');
    }

    /* ====================================================
       📦 EXPORTACIÓN COMPLETA (ZIP)
       ==================================================== */

    public function exportTodo()
    {
        $tmpFolder = storage_path('app/exports_tmp_' . Str::random(6));
        mkdir($tmpFolder);

        // Crear todos los Excel
        (new FastExcel($this->mapClientes()))->export($tmpFolder . '/clientes.xlsx');
        (new FastExcel($this->mapPedidos()))->export($tmpFolder . '/pedidos.xlsx');
        (new FastExcel($this->mapPagos()))->export($tmpFolder . '/pagos.xlsx');
        (new FastExcel($this->mapPagosConsolidados()))->export($tmpFolder . '/pagos_consolidados.xlsx');
        (new FastExcel($this->mapPlatillos()))->export($tmpFolder . '/platillos.xlsx');
        (new FastExcel($this->mapPagoConsolidadoPedidos()))->export($tmpFolder . '/pago_consolidado_pedidos.xlsx');

        // Comprimir en ZIP
        $zipPath = storage_path('app/respaldo_restaurante.zip');
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach (glob($tmpFolder . '/*.xlsx') as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }

        // Eliminar archivos temporales
        foreach (glob($tmpFolder . '/*.xlsx') as $file) {
            unlink($file);
        }
        rmdir($tmpFolder);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /* ====================================================
       📋 MÉTODOS REUTILIZABLES PARA EXPORT TODO
       ==================================================== */

    private function mapClientes()
    {
        return Cliente::all()->map(fn($c) => [
            'ID' => $c->id,
            'Nombre' => $c->nombre,
            'Teléfono' => $c->telefono,
        ]);
    }

    private function mapPedidos()
    {
        return Pedido::with('cliente')->get()->map(fn($p) => [
            'ID Pedido' => $p->id,
            'Cliente' => $p->cliente->nombre ?? 'N/A',
            'Total' => $p->total,
            'Estado' => $p->estado,
            'Fecha Pedido' => $p->fecha_pedido,
        ]);
    }

    private function mapPagos()
    {
        return Pago::with('pedido.cliente')->get()->map(fn($p) => [
            'ID Pago' => $p->id,
            'Cliente' => $p->pedido->cliente->nombre ?? 'N/A',
            'Método Pago' => $p->metodo_pago,
            'Estado Pago' => $p->estado_pago,
            'Fecha Pago' => $p->fecha_pago,
        ]);
    }

    private function mapPagosConsolidados()
    {
        return PagoConsolidado::with('cliente')->get()->map(fn($p) => [
            'ID' => $p->id,
            'Cliente' => $p->cliente->nombre ?? 'N/A',
            'Monto Total' => $p->monto_total,
            'Método Pago' => $p->metodo_pago,
            'Estado Pago' => $p->estado_pago,
        ]);
    }

    private function mapPlatillos()
    {
        return Platillo::all()->map(fn($p) => [
            'ID' => $p->id,
            'Nombre' => $p->nombre,
            'Precio Base' => $p->precio_base,
            'Activo' => $p->activo ? 'Sí' : 'No',
        ]);
    }

    private function mapPagoConsolidadoPedidos()
    {
        return PagoConsolidadoPedido::with(['pagoConsolidado.cliente', 'pedido.cliente'])
            ->get()
            ->map(function ($rel) {
                return [
                    'ID Relación' => $rel->id,
                    'Pago Consolidado ID' => $rel->pago_consolidado_id,
                    'Cliente del Pago' => $rel->pagoConsolidado?->cliente?->nombre ?? 'N/A',
                    'Pedido ID' => $rel->pedido_id,
                    'Cliente del Pedido' => $rel->pedido?->cliente?->nombre ?? 'N/A',
                    'Pagado' => $rel->pagado ? 'Sí' : 'No',
                    'Fecha Creación' => optional($rel->created_at)->format('Y-m-d H:i'),
                ];
            });
    }

}
