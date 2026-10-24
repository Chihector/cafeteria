<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_venta_descuenta_stock_y_registra_el_movimiento(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->crearMesa();
        $producto = $this->crearProducto(['stock' => 5]);

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 2,
            ]],
        ])->assertCreated();

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 3]);
        $this->assertDatabaseHas('inventario_movimientos', [
            'producto_id' => $producto->id,
            'tipo' => 'venta',
            'cantidad' => 2,
            'stock_anterior' => 5,
            'stock_nuevo' => 3,
        ]);
    }

    public function test_una_venta_sin_stock_no_crea_pedido_ni_ocupa_la_mesa(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->crearMesa();
        $producto = $this->crearProducto(['stock' => 2]);

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 3,
            ]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('productos');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 2]);
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'disponible']);
    }

    public function test_las_lineas_duplicadas_suman_su_cantidad_para_validar_stock(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        $mesa = $this->crearMesa();
        $producto = $this->crearProducto(['stock' => 5]);

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad' => 3],
                ['producto_id' => $producto->id, 'cantidad' => 3],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('productos');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 5]);
    }

    public function test_el_admin_puede_registrar_entradas_de_inventario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $producto = $this->crearProducto(['stock' => 4]);

        $this->actingAs($admin)
            ->post("/admin/inventario/{$producto->id}/entrada", [
                'cantidad' => 10,
                'motivo' => 'Compra a proveedor',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 14]);
        $this->assertDatabaseHas('inventario_movimientos', [
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'cantidad' => 10,
            'stock_anterior' => 4,
            'stock_nuevo' => 14,
            'motivo' => 'Compra a proveedor',
        ]);
    }

    public function test_el_admin_puede_ajustar_el_stock_a_un_valor_exacto(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $producto = $this->crearProducto(['stock' => 14]);

        $this->actingAs($admin)
            ->post("/admin/inventario/{$producto->id}/ajuste", [
                'stock' => 6,
                'motivo' => 'Conteo físico',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 6]);
        $this->assertDatabaseHas('inventario_movimientos', [
            'producto_id' => $producto->id,
            'tipo' => 'ajuste',
            'cantidad' => -8,
            'stock_anterior' => 14,
            'stock_nuevo' => 6,
        ]);
    }

    public function test_no_se_puede_eliminar_un_producto_con_movimientos_de_inventario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $producto = $this->crearProducto(['stock' => 5]);

        $producto->movimientos()->create([
            'user_id' => $admin->id,
            'tipo' => 'entrada',
            'cantidad' => 5,
            'stock_anterior' => 0,
            'stock_nuevo' => 5,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/productos/{$producto->id}")
            ->assertRedirect('/admin/productos')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
    }

    public function test_el_pos_y_la_carta_ocultan_los_productos_agotados(): void
    {
        $barista = User::factory()->create(['role' => 'barista']);
        $agotado = $this->crearProducto(['nombre' => 'Agotado', 'stock' => 0]);
        $disponible = $this->crearProducto(['nombre' => 'Disponible', 'stock' => 3]);

        $this->actingAs($barista)
            ->getJson('/api/productos')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $disponible->id);

        $this->actingAs($barista)
            ->get('/carta')
            ->assertOk()
            ->assertDontSee($agotado->nombre)
            ->assertSee($disponible->nombre);
    }

    private function crearMesa(): Mesa
    {
        return Mesa::create([
            'numero' => '1',
            'capacidad' => 4,
            'estado' => 'disponible',
        ]);
    }

    private function crearProducto(array $atributos = []): Producto
    {
        return Producto::create(array_merge([
            'nombre' => 'Latte',
            'precio' => '2500.00',
            'categoria' => 'coffee',
            'disponible' => true,
            'controlar_stock' => true,
            'stock' => 10,
            'stock_minimo' => 2,
        ], $atributos));
    }
}
