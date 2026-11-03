<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_barista_who_can_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Barista de Prueba',
            'email' => 'nuevo.barista@test.com',
            'role' => 'barista',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/admin/usuarios');

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo.barista@test.com',
            'role' => 'barista',
            'activo' => true,
        ]);

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'nuevo.barista@test.com',
            'password' => 'password123',
        ])->assertRedirect('/pos');
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $barista = User::factory()->create(['role' => 'barista', 'activo' => false]);

        $this->post('/login', [
            'email' => $barista->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barista = User::factory()->create(['role' => 'barista', 'activo' => true]);

        $this->actingAs($admin)->post("/admin/usuarios/{$barista->id}/toggle")
            ->assertRedirect('/admin/usuarios');

        $this->assertDatabaseHas('users', ['id' => $barista->id, 'activo' => false]);

        $this->post('/logout');
        $this->post('/login', ['email' => $barista->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)->post("/admin/usuarios/{$barista->id}/toggle");

        $this->assertDatabaseHas('users', ['id' => $barista->id, 'activo' => true]);

        $this->post('/logout');
        $this->post('/login', ['email' => $barista->email, 'password' => 'password'])
            ->assertRedirect('/pos');
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post("/admin/usuarios/{$admin->id}/toggle");

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'activo' => true]);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put("/admin/usuarios/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'barista',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_can_reset_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barista = User::factory()->create(['role' => 'barista']);

        $this->actingAs($admin)->post("/admin/usuarios/{$barista->id}/reset-password", [
            'password' => 'nuevaClaveSegura',
            'password_confirmation' => 'nuevaClaveSegura',
        ])->assertRedirect('/admin/usuarios');

        $this->post('/logout');

        $this->post('/login', ['email' => $barista->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $barista->email, 'password' => 'nuevaClaveSegura'])
            ->assertRedirect('/pos');
    }

    public function test_reset_password_requires_eight_characters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barista = User::factory()->create(['role' => 'barista']);

        $this->actingAs($admin)->post("/admin/usuarios/{$barista->id}/reset-password", [
            'password' => 'corta1',
            'password_confirmation' => 'corta1',
        ])->assertSessionHasErrors('password');
    }

    public function test_user_creation_is_audited_without_passwords(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Cocina Uno',
            'email' => 'cocina.uno@test.com',
            'role' => 'cocina',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/admin/usuarios');

        $auditoria = Auditoria::where('accion', 'crear')->where('modulo', 'usuarios')->first();
        $this->assertNotNull($auditoria);
        $this->assertStringContainsString('cocina.uno@test.com', $auditoria->descripcion);
        $this->assertStringNotContainsString('password', json_encode($auditoria->cambios));
    }

    public function test_admin_can_view_the_user_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/usuarios')->assertOk();
        $this->actingAs($admin)->get('/admin/usuarios/create')->assertOk();
        $this->actingAs($admin)->get("/admin/usuarios/{$admin->id}/edit")->assertOk();
    }

    public function test_barista_cannot_access_user_management(): void
    {
        $barista = User::factory()->create(['role' => 'barista']);

        $this->actingAs($barista)->get('/admin/usuarios')->assertForbidden();
    }

    public function test_admin_can_deactivate_another_admin_when_one_remains(): void
    {
        $adminA = User::factory()->create(['role' => 'admin']);
        $adminB = User::factory()->create(['role' => 'admin']);

        $this->actingAs($adminA)->post("/admin/usuarios/{$adminB->id}/toggle");

        $this->assertDatabaseHas('users', ['id' => $adminB->id, 'activo' => false]);
        $this->assertDatabaseHas('users', ['id' => $adminA->id, 'activo' => true]);
    }
}
