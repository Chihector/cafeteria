<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteZCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_requires_authentication(): void
    {
        $this->get('/admin/cierre-turno/csv')->assertRedirect('/login');
    }

    public function test_csv_export_contains_summary_breakdown_and_paid_orders_of_the_day(): void
    {
        $user = User::factory()->create(['name' => 'Carlos Muñoz', 'role' => 'admin']);
        $mesa = Mesa::create(['numero' => '5', 'capacidad' => 4, 'estado' => 'disponible']);

        Pedido::create([
            'mesa_id' => $mesa->id,
            'user_id' => $user->id,
            'estado' => 'pagado',
            'total' => 1250,
            'propina' => 250,
            'metodo_pago' => 'efectivo',
            'created_at' => now(),
        ]);

        // Pedido no pagado: no debe aparecer en el reporte
        Pedido::create([
            'mesa_id' => $mesa->id,
            'user_id' => $user->id,
            'estado' => 'pendiente',
            'total' => 9999,
            'created_at' => now(),
        ]);

        // Pedido pagado de otro día: no debe aparecer en el reporte del día
        // (created_at no es fillable, se asigna después del create)
        $pedidoDeAyer = Pedido::create([
            'mesa_id' => $mesa->id,
            'user_id' => $user->id,
            'estado' => 'pagado',
            'total' => 7777,
            'propina' => 0,
            'metodo_pago' => 'tarjeta',
        ]);
        $pedidoDeAyer->created_at = now()->subDay();
        $pedidoDeAyer->save();

        $response = $this->actingAs($user)
            ->get('/admin/cierre-turno/csv?periodo=dia&valor='.now()->toDateString());

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString(
            'reporte-z-dia-'.now()->format('Ymd').'.csv',
            $response->headers->get('content-disposition')
        );

        $contenido = $response->streamedContent();

        // Resumen
        $this->assertStringContainsString('Café Rosé - Reporte Z', $contenido);
        $this->assertStringContainsString('Ventas Netas', $contenido);
        $this->assertStringContainsString('1.250,00', $contenido);
        $this->assertStringContainsString('250,00', $contenido);
        $this->assertStringContainsString('1.500,00', $contenido);

        // Desglose por método de pago
        $this->assertStringContainsString('DESGLOSE POR MÉTODO DE PAGO', $contenido);
        $this->assertStringContainsString('Efectivo', $contenido);
        $this->assertStringContainsString('Tarjeta', $contenido);
        $this->assertStringContainsString('Transferencia', $contenido);

        // Detalle del pedido pagado del día
        $this->assertStringContainsString('DETALLE DE PEDIDOS PAGADOS', $contenido);
        $this->assertStringContainsString('Mesa 5', $contenido);
        $this->assertStringContainsString('Carlos Muñoz', $contenido);

        // Exclusiones
        $this->assertStringNotContainsString('9.999,00', $contenido);
        $this->assertStringNotContainsString('7.777,00', $contenido);
    }
}
