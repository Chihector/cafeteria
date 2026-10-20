<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_uses_catalog_price_and_occupies_the_table(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->createMesa();
        $producto = $this->createProducto('1250.50');

        $response = $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 2,
                'precio_unitario' => 1,
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('pedido.total', 2501)
            ->assertJsonPath('pedido.detalles.0.precio_unitario', 1250.5);

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'ocupada']);
        $this->assertDatabaseHas('pedidos', [
            'mesa_id' => $mesa->id,
            'total' => '2501.00',
            'estado' => 'pendiente',
        ]);
    }

    public function test_order_rejects_an_occupied_table(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->createMesa('ocupada');
        $producto = $this->createProducto();

        $this->postJson('/api/pedidos', $this->orderPayload($mesa, $producto))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mesa_id');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_order_rejects_a_table_with_an_active_order_even_if_marked_available(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->createMesa();
        $producto = $this->createProducto();
        Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pendiente',
            'total' => 1000,
        ]);

        $this->postJson('/api/pedidos', $this->orderPayload($mesa, $producto))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mesa_id');

        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_order_rejects_an_unavailable_product_without_changing_the_table(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->createMesa();
        $producto = $this->createProducto('1000.00', false);

        $this->postJson('/api/pedidos', $this->orderPayload($mesa, $producto))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('productos.0.producto_id');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'disponible']);
    }

    public function test_payment_marks_order_paid_and_releases_its_table_once(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [$mesa, $pedido] = $this->createActiveOrder();

        $this->postJson("/api/pedidos/{$pedido->id}/pagar", [
            'metodo_pago' => 'efectivo',
            'propina' => 250,
        ])->assertOk()
            ->assertJsonPath('pedido.estado', 'pagado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => 'pagado',
            'metodo_pago' => 'efectivo',
            'propina' => '250.00',
        ]);
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'disponible']);

        $this->postJson("/api/pedidos/{$pedido->id}/pagar", [
            'metodo_pago' => 'efectivo',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');
    }

    public function test_status_endpoint_cannot_bypass_payment_processing(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cocina']));
        [, $pedido] = $this->createActiveOrder();

        $this->patchJson("/api/kds/pedidos/{$pedido->id}/estado", [
            'estado' => 'pagado',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('estado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => 'pendiente',
            'metodo_pago' => null,
        ]);
    }

    public function test_paid_order_cannot_be_reopened_from_the_kitchen_status_endpoint(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cocina']));
        [, $pedido] = $this->createActiveOrder();
        $pedido->update(['estado' => 'pagado', 'metodo_pago' => 'efectivo']);

        $this->patchJson("/api/kds/pedidos/{$pedido->id}/estado", [
            'estado' => 'preparando',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => 'pagado',
        ]);
    }

    private function createMesa(string $estado = 'disponible'): Mesa
    {
        return Mesa::create([
            'numero' => (string) (Mesa::count() + 1),
            'capacidad' => 4,
            'estado' => $estado,
        ]);
    }

    private function createProducto(string $precio = '1000.00', bool $disponible = true): Producto
    {
        return Producto::create([
            'nombre' => 'Producto de prueba',
            'precio' => $precio,
            'categoria' => 'coffee',
            'disponible' => $disponible,
        ]);
    }

    private function createActiveOrder(): array
    {
        $mesa = $this->createMesa('ocupada');
        $pedido = Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pendiente',
            'total' => 1000,
        ]);

        return [$mesa, $pedido];
    }

    private function orderPayload(Mesa $mesa, Producto $producto): array
    {
        return [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ]],
        ];
    }
}
