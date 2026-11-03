<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnulacionPedidoTest extends TestCase
{
    use RefreshDatabase;

    private const MOTIVO = 'Cliente se retiró sin ordenar';

    public function test_anular_repone_stock_y_libera_la_mesa(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->createMesa();
        $producto = $this->createProductoConStock(10);

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad' => 3],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 7]);

        $pedido = Pedido::firstOrFail();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertOk()
            ->assertJsonPath('estado', 'anulado')
            ->assertJsonPath('mesa_estado', 'disponible');

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 10]);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'anulado']);
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'disponible']);

        $this->assertDatabaseHas('inventario_movimientos', [
            'pedido_id' => $pedido->id,
            'tipo' => 'devolucion',
            'cantidad' => 3,
            'stock_anterior' => 7,
            'stock_nuevo' => 10,
        ]);
    }

    public function test_anular_registra_auditoria_con_motivo(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->createMesa();
        $producto = $this->createProductoConStock(5);

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertCreated();

        $pedido = Pedido::firstOrFail();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertOk();

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'anular',
            'modulo' => 'pedidos',
            'modelo_id' => $pedido->id,
        ]);

        $auditoria = Auditoria::where('accion', 'anular')->first();
        $this->assertStringContainsString(self::MOTIVO, $auditoria->cambios['motivo'] ?? '');
    }

    public function test_anular_es_idempotente_una_sola_vez(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [$mesa, $pedido] = $this->createActiveOrder();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertOk();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');
    }

    public function test_pedido_pagado_no_se_puede_anular(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [, $pedido] = $this->createActiveOrder();
        $pedido->update(['estado' => 'pagado', 'metodo_pago' => 'efectivo']);

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'pagado']);
    }

    public function test_motivo_es_obligatorio_y_minimo_cinco_caracteres(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [, $pedido] = $this->createActiveOrder();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motivo');

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => 'abcd'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motivo');
    }

    public function test_cocina_no_puede_anular_un_pedido(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cocina']));
        [, $pedido] = $this->createActiveOrder();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertForbidden();

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'pendiente']);
    }

    public function test_mesa_permanece_ocupada_si_otro_pedido_activo_sigue(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->createMesa('ocupada');

        // Dos pedidos activos en la misma mesa (creados directamente para forzar el escenario).
        $pedidoA = Pedido::create(['mesa_id' => $mesa->id, 'estado' => 'pendiente', 'total' => 1000]);
        Pedido::create(['mesa_id' => $mesa->id, 'estado' => 'preparando', 'total' => 2000]);

        $this->postJson("/api/pedidos/{$pedidoA->id}/anular", ['motivo' => self::MOTIVO])
            ->assertOk()
            ->assertJsonPath('mesa_estado', 'ocupada');

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'ocupada']);
    }

    public function test_pedido_anulado_no_aparece_en_la_cocina(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [$mesa, $pedido] = $this->createActiveOrder();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'cocina']));

        $this->getJson('/api/kds/pedidos?estado=pendiente')
            ->assertOk()
            ->assertJsonMissing(['id' => $pedido->id]);
    }

    public function test_pedido_anulado_tampoco_acepta_cambios_de_estado(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [, $pedido] = $this->createActiveOrder();

        $this->postJson("/api/pedidos/{$pedido->id}/anular", ['motivo' => self::MOTIVO])
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'cocina']));

        $this->patchJson("/api/kds/pedidos/{$pedido->id}/estado", ['estado' => 'preparando'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');
    }

    private function createMesa(string $estado = 'disponible'): Mesa
    {
        return Mesa::create([
            'numero' => (string) (Mesa::count() + 1),
            'capacidad' => 4,
            'estado' => $estado,
        ]);
    }

    private function createProductoConStock(int $stock): Producto
    {
        return Producto::create([
            'nombre' => 'Producto de prueba '.uniqid(),
            'precio' => 1000,
            'categoria' => 'coffee',
            'disponible' => true,
            'controlar_stock' => true,
            'stock' => $stock,
            'stock_minimo' => 1,
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
}
