<?php

namespace Tests\Feature;

use App\Events\PedidoActualizado;
use App\Events\PedidoCreado;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Restaura el broadcaster "null" de los tests para no contaminar otras suites.
        config(['broadcasting.default' => 'null']);
        app(BroadcastManager::class)->forgetDrivers();

        parent::tearDown();
    }

    public function test_creating_an_order_broadcasts_pedido_creado_to_pos_and_kitchen(): void
    {
        Event::fake([PedidoCreado::class]);

        $barista = User::factory()->create(['role' => 'barista']);
        $mesa = $this->crearMesa();
        $producto = $this->crearProducto();

        $this->actingAs($barista)->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 2,
            ]],
        ])->assertCreated();

        Event::assertDispatched(PedidoCreado::class, function (PedidoCreado $event) use ($mesa) {
            $canales = $this->nombresCanales($event);

            return $event->pedido->mesa_id === $mesa->id
                && in_array('private-pos', $canales, true)
                && in_array('private-cocina', $canales, true);
        });
    }

    public function test_status_change_broadcasts_pedido_actualizado(): void
    {
        Event::fake([PedidoActualizado::class]);

        $cocina = User::factory()->create(['role' => 'cocina']);
        [, $pedido] = $this->crearPedidoActivo();

        $this->actingAs($cocina)
            ->patchJson("/api/kds/pedidos/{$pedido->id}/estado", ['estado' => 'preparando'])
            ->assertOk();

        Event::assertDispatched(PedidoActualizado::class, function (PedidoActualizado $event) use ($pedido) {
            return $event->pedidoId === $pedido->id
                && $event->estado === 'preparando'
                && $event->mesaEstado === 'ocupada';
        });
    }

    public function test_payment_broadcasts_table_release(): void
    {
        Event::fake([PedidoActualizado::class]);

        $barista = User::factory()->create(['role' => 'barista']);
        [$mesa, $pedido] = $this->crearPedidoActivo();

        $this->actingAs($barista)
            ->postJson("/api/pedidos/{$pedido->id}/pagar", ['metodo_pago' => 'efectivo'])
            ->assertOk();

        Event::assertDispatched(PedidoActualizado::class, function (PedidoActualizado $event) use ($pedido, $mesa) {
            return $event->pedidoId === $pedido->id
                && $event->estado === 'pagado'
                && $event->mesaId === $mesa->id
                && $event->mesaEstado === 'disponible';
        });
    }

    public function test_event_payload_and_broadcast_names(): void
    {
        $mesa = $this->crearMesa();
        $pedido = Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pendiente',
            'total' => 2500,
        ]);

        $creado = new PedidoCreado($pedido);

        $this->assertSame('pedido.creado', $creado->broadcastAs());
        $this->assertSame(['private-pos', 'private-cocina'], $this->nombresCanales($creado));
        $this->assertSame($pedido->id, $creado->broadcastWith()['pedido_id']);
        $this->assertSame($mesa->numero, $creado->broadcastWith()['mesa_numero']);

        $actualizado = new PedidoActualizado($pedido->id, $mesa->id, 'pagado', 'disponible');

        $this->assertSame('pedido.actualizado', $actualizado->broadcastAs());
        $this->assertSame(['private-pos', 'private-cocina'], $this->nombresCanales($actualizado));
        $this->assertSame([
            'pedido_id' => $pedido->id,
            'mesa_id' => $mesa->id,
            'estado' => 'pagado',
            'mesa_estado' => 'disponible',
        ], $actualizado->broadcastWith());
    }

    public function test_channel_authorization_respects_roles(): void
    {
        // El broadcaster "null" de los tests no valida canales; usamos el de Pusher
        // (el mismo protocolo que Reverb) para ejercitar la autorización real.
        $this->usarBroadcasterPusher();

        $barista = User::factory()->create(['role' => 'barista']);
        $cocina = User::factory()->create(['role' => 'cocina']);
        $admin = User::factory()->create(['role' => 'admin']);
        $peticion = [
            'channel_name' => 'private-pos',
            'socket_id' => '1234.5678',
        ];

        // Barista: puede entrar a "pos", no a "cocina".
        $this->actingAs($barista)->post('/broadcasting/auth', $peticion)->assertOk();
        $this->post('/broadcasting/auth', [
            'channel_name' => 'private-cocina',
            'socket_id' => '1234.5678',
        ])->assertForbidden();

        // Cocina: al revés.
        $this->actingAs($cocina)->post('/broadcasting/auth', [
            'channel_name' => 'private-cocina',
            'socket_id' => '1234.5678',
        ])->assertOk();
        $this->post('/broadcasting/auth', $peticion)->assertForbidden();

        // Admin: ambos canales.
        $this->actingAs($admin)->post('/broadcasting/auth', $peticion)->assertOk();
        $this->post('/broadcasting/auth', [
            'channel_name' => 'private-cocina',
            'socket_id' => '1234.5678',
        ])->assertOk();
    }

    public function test_guest_cannot_authorize_channels(): void
    {
        $this->usarBroadcasterPusher();

        $this->post('/broadcasting/auth', [
            'channel_name' => 'private-pos',
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    /**
     * Configura una conexión Pusher local (sin red) para validar canales.
     */
    private function usarBroadcasterPusher(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'driver' => 'pusher',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => 'test-app',
                'options' => [
                    'cluster' => 'mt1',
                    'host' => '127.0.0.1',
                    'port' => 8080,
                    'scheme' => 'http',
                    'encrypted' => false,
                    'useTLS' => false,
                ],
            ],
        ]);

        $manager = app(BroadcastManager::class);
        $manager->forgetDrivers();
        $manager->driver();

        // Los canales se registraron al arrancar sobre el driver "null" de tests;
        // los volvemos a registrar sobre el nuevo broadcaster.
        require base_path('routes/channels.php');
    }

    /**
     * @return array<int, string>
     */
    private function nombresCanales(object $event): array
    {
        return array_map(fn ($canal) => (string) $canal, $event->broadcastOn());
    }

    private function crearMesa(): Mesa
    {
        return Mesa::create([
            'numero' => '1',
            'capacidad' => 4,
            'estado' => 'disponible',
        ]);
    }

    private function crearProducto(): Producto
    {
        return Producto::create([
            'nombre' => 'Cappuccino',
            'precio' => '1250.50',
            'categoria' => 'coffee',
            'disponible' => true,
        ]);
    }

    /**
     * @return array{0: Mesa, 1: Pedido}
     */
    private function crearPedidoActivo(): array
    {
        $mesa = $this->crearMesa();
        $mesa->update(['estado' => 'ocupada']);

        $pedido = Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pendiente',
            'total' => 1250.50,
        ]);

        return [$mesa, $pedido];
    }
}
