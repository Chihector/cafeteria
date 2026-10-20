<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se emite cuando una comanda cambia de estado o se cobra.
 * Mantiene sincronizados el KDS y el grid de mesas del POS.
 */
class PedidoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $pedidoId,
        public int $mesaId,
        public string $estado,
        public string $mesaEstado,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('pos'),
            new PrivateChannel('cocina'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pedido.actualizado';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'pedido_id' => $this->pedidoId,
            'mesa_id' => $this->mesaId,
            'estado' => $this->estado,
            'mesa_estado' => $this->mesaEstado,
        ];
    }
}
