<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_un_producto_registra_auditoria(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/admin/productos', [
                'nombre' => 'Matcha Latte',
                'precio' => 4200,
                'categoria' => 'cold_drinks',
                'disponible' => '1',
            ])
            ->assertRedirect('/admin/productos');

        $this->assertDatabaseHas('auditorias', [
            'user_id' => $admin->id,
            'accion' => 'crear',
            'modulo' => 'productos',
        ]);
    }

    public function test_el_login_y_el_cobro_registran_auditoria(): void
    {
        $barista = User::factory()->create(['role' => 'barista']);

        $this->post('/login', [
            'email' => $barista->email,
            'password' => 'password',
        ])->assertRedirect('/pos');

        $this->assertDatabaseHas('auditorias', [
            'user_id' => $barista->id,
            'accion' => 'login',
            'modulo' => 'auth',
        ]);

        $mesa = Mesa::create(['numero' => '1', 'capacidad' => 4, 'estado' => 'ocupada']);
        $producto = Producto::create([
            'nombre' => 'Latte',
            'precio' => '2500.00',
            'categoria' => 'coffee',
            'disponible' => true,
        ]);
        $pedido = Pedido::create([
            'mesa_id' => $mesa->id,
            'user_id' => $barista->id,
            'estado' => 'pendiente',
            'total' => 2500,
        ]);
        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => $producto->precio,
        ]);

        $this->actingAs($barista)
            ->postJson("/api/pedidos/{$pedido->id}/pagar", [
                'metodo_pago' => 'tarjeta',
            ])
            ->assertOk();

        $this->assertDatabaseHas('auditorias', [
            'user_id' => $barista->id,
            'accion' => 'cobrar',
            'modulo' => 'pedidos',
        ]);
    }

    public function test_el_admin_puede_ver_y_filtrar_la_auditoria(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Auditoria::create([
            'user_id' => $admin->id,
            'accion' => 'crear',
            'modulo' => 'productos',
            'descripcion' => 'Producto creado: Latte',
            'ip' => '127.0.0.1',
        ]);
        Auditoria::create([
            'user_id' => $admin->id,
            'accion' => 'cobrar',
            'modulo' => 'pedidos',
            'descripcion' => 'Pedido #99 cobrado',
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get('/admin/auditoria')
            ->assertOk()
            ->assertSee('Producto creado: Latte')
            ->assertSee('Pedido #99 cobrado');

        $this->actingAs($admin)
            ->get('/admin/auditoria?modulo=productos')
            ->assertOk()
            ->assertSee('Producto creado: Latte')
            ->assertDontSee('Pedido #99 cobrado');
    }

    public function test_un_barista_no_puede_ver_la_auditoria(): void
    {
        $barista = User::factory()->create(['role' => 'barista']);

        $this->actingAs($barista)
            ->get('/admin/auditoria')
            ->assertForbidden();
    }
}
