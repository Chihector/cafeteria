<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_protected_routes(): void
    {
        $rutas = [
            '/admin',
            '/admin/productos',
            '/admin/cierre-turno',
            '/admin/cierre-turno/csv',
            '/admin/inventario',
            '/admin/auditoria',
            '/admin/impresora',
            '/pos',
            '/kds',
            '/api/mesas',
            '/api/productos',
            '/api/kds/pedidos',
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertRedirect('/login');
        }
    }

    public function test_login_redirects_each_role_to_its_home(): void
    {
        $destinos = [
            'admin' => '/admin',
            'barista' => '/pos',
            'cocina' => '/kds',
        ];

        foreach ($destinos as $role => $home) {
            $user = User::factory()->create(['role' => $role]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect($home);

            $this->post('/logout');
        }
    }

    public function test_root_redirects_authenticated_users_by_role(): void
    {
        $destinos = [
            'admin' => '/admin',
            'barista' => '/pos',
            'cocina' => '/kds',
        ];

        foreach ($destinos as $role => $home) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/')->assertRedirect($home);
        }
    }

    public function test_admin_can_access_every_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pedido = $this->crearPedido();

        $this->actingAs($admin);

        $this->get('/admin')->assertOk();
        $this->get('/admin/productos')->assertOk();
        $this->get('/admin/cierre-turno')->assertOk();
        $this->get('/admin/cierre-turno/csv')->assertOk();
        $this->get('/admin/inventario')->assertOk();
        $this->get('/admin/auditoria')->assertOk();
        $this->get('/admin/impresora')->assertOk();
        $this->get('/pos')->assertOk();
        $this->get('/kds')->assertOk();
        $this->get('/api/mesas')->assertOk();
        $this->get('/api/productos')->assertOk();
        $this->get('/api/kds/pedidos')->assertOk();
        $this->get("/api/pedidos/{$pedido->id}/ticket")->assertOk();
        $this->get("/api/pedidos/{$pedido->id}/comanda")->assertOk();
    }

    public function test_barista_can_use_pos_but_not_admin_or_kitchen(): void
    {
        $barista = User::factory()->create(['role' => 'barista']);
        $pedido = $this->crearPedido();

        $this->actingAs($barista);

        // Permitido
        $this->get('/pos')->assertOk();
        $this->get('/api/mesas')->assertOk();
        $this->get('/api/productos')->assertOk();
        $this->get("/api/pedidos/{$pedido->id}/ticket")->assertOk();
        $this->get("/api/pedidos/{$pedido->id}/comanda")->assertOk();

        // Denegado
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/productos')->assertForbidden();
        $this->get('/admin/cierre-turno')->assertForbidden();
        $this->get('/admin/cierre-turno/csv')->assertForbidden();
        $this->get('/admin/inventario')->assertForbidden();
        $this->get('/admin/auditoria')->assertForbidden();
        $this->get('/admin/impresora')->assertForbidden();
        $this->get('/kds')->assertForbidden();
        $this->get('/api/kds/pedidos')->assertForbidden();
        $this->patchJson("/api/kds/pedidos/{$pedido->id}/estado", [
            'estado' => 'preparando',
        ])->assertForbidden();
    }

    public function test_cocina_can_use_kitchen_but_not_pos_or_admin(): void
    {
        $cocina = User::factory()->create(['role' => 'cocina']);
        $pedido = $this->crearPedido();

        $this->actingAs($cocina);

        // Permitido
        $this->get('/kds')->assertOk();
        $this->get('/api/kds/pedidos')->assertOk();
        $this->patchJson("/api/kds/pedidos/{$pedido->id}/estado", [
            'estado' => 'preparando',
        ])->assertOk();
        $this->get("/api/pedidos/{$pedido->id}/comanda")->assertOk();

        // Denegado
        $this->get('/pos')->assertForbidden();
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/productos')->assertForbidden();
        $this->get('/admin/cierre-turno')->assertForbidden();
        $this->get('/admin/cierre-turno/csv')->assertForbidden();
        $this->get('/admin/inventario')->assertForbidden();
        $this->get('/admin/auditoria')->assertForbidden();
        $this->get('/admin/impresora')->assertForbidden();
        $this->get('/api/mesas')->assertForbidden();
        $this->get('/api/productos')->assertForbidden();
        $this->postJson('/api/pedidos', [])->assertForbidden();
        $this->postJson("/api/pedidos/{$pedido->id}/pagar", [
            'metodo_pago' => 'efectivo',
        ])->assertForbidden();
        $this->get("/api/pedidos/{$pedido->id}/ticket")->assertForbidden();
    }

    private function crearPedido(): Pedido
    {
        $mesa = Mesa::create([
            'numero' => '1',
            'capacidad' => 4,
            'estado' => 'ocupada',
        ]);

        $producto = Producto::create([
            'nombre' => 'Cappuccino',
            'precio' => '2900.00',
            'categoria' => 'coffee',
            'disponible' => true,
        ]);

        $pedido = Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pendiente',
            'total' => 2900,
        ]);

        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => $producto->precio,
        ]);

        return $pedido;
    }
}
