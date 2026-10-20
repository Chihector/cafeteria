<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_only_paid_orders_as_sales(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mesa = $this->crearMesa();

        // Venta real del día
        Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pagado',
            'total' => 1200,
            'metodo_pago' => 'efectivo',
        ]);

        // Pedidos activos: no son ventas todavía
        Pedido::create(['mesa_id' => $mesa->id, 'estado' => 'pendiente', 'total' => 5000]);
        Pedido::create(['mesa_id' => $mesa->id, 'estado' => 'entregado', 'total' => 3000]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertViewHas('ventasHoy', fn ($valor) => (float) $valor === 1200.0)
            ->assertViewHas('pedidosHoy', 3);
    }

    public function test_product_with_order_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mesa = $this->crearMesa();
        $producto = $this->crearProducto();

        $pedido = Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pagado',
            'total' => 1000,
            'metodo_pago' => 'efectivo',
        ]);
        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 1000,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/productos/{$producto->id}")
            ->assertRedirect('/admin/productos')
            ->assertSessionHas('error');

        // El producto y la línea histórica siguen intactos.
        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
        $this->assertDatabaseHas('detalle_pedidos', ['producto_id' => $producto->id]);
    }

    public function test_product_without_order_history_can_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $producto = $this->crearProducto();

        $this->actingAs($admin)
            ->delete("/admin/productos/{$producto->id}")
            ->assertRedirect('/admin/productos')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    public function test_product_show_route_is_not_registered(): void
    {
        // La ruta productos.show no debe existir (no hay vista de detalle).
        $this->assertFalse(Route::has('productos.show'));

        $admin = User::factory()->create(['role' => 'admin']);
        $producto = $this->crearProducto();

        // La URI solo acepta PUT/PATCH/DELETE, no GET.
        $this->actingAs($admin)
            ->get("/admin/productos/{$producto->id}")
            ->assertStatus(405);
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
            'nombre' => 'Latte',
            'precio' => '1000.00',
            'categoria' => 'coffee',
            'disponible' => true,
        ]);
    }
}
