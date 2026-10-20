<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se emite cuando el POS crea una comanda nueva.
 * El KDS la muestra al instante; el POS refresca el estado de las mesas.
 */
class PedidoCreado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Pedido $pedido) {}

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
        return 'pedido.creado';
    }

    /**
     * Payload mínimo: el cliente refresca sus datos por HTTP para evitar duplicar lógica.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'pedido_id' => $this->pedido->id,
            'mesa_id' => $this->pedido->mesa_id,
            'mesa_numero' => $this->pedido->mesa?->numero,
            'estado' => $this->pedido->estado,
            'total' => (float) $this->pedido->total,
        ];
    }
}
