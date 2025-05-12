<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pedido;

class PedidosStatus extends Component
{
    use WithPagination;

    public $pedidoSeleccionado = null;
    public $nuevoEstado = '';
    public $estados = ['pendiente', 'en preparación', 'despachado', 'entregado', 'cancelado'];

    protected $paginationTheme = 'tailwind'; // Para que la paginación use estilos Tailwind

    public function seleccionarPedido($id)
    {
        $this->pedidoSeleccionado = Pedido::with('cliente', 'detalles.platillo', 'pago')->findOrFail($id);
        $this->nuevoEstado = $this->pedidoSeleccionado->estado;
    }

    public function actualizarEstado()
    {
        if (!$this->pedidoSeleccionado) {
            session()->flash('error', 'No hay pedido seleccionado.');
            return;
        }

        $this->validate([
            'nuevoEstado' => 'required|in:' . implode(',', $this->estados),
        ]);

        $this->pedidoSeleccionado->estado = $this->nuevoEstado;
        $this->pedidoSeleccionado->save();

        session()->flash('success', 'Estado actualizado correctamente.');
    }

    public function render()
    {
        $pedidos = Pedido::with('cliente')
            ->orderBy('fecha_pedido', 'desc')
            ->paginate(10);

        return view('livewire.admin.pedidosStatus', compact('pedidos'));
    }
}
