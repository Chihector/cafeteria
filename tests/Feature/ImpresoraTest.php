<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Services\Printing\EscPosFormatter;
use App\Services\Printing\PrinterTransport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ImpresoraTest extends TestCase
{
    use RefreshDatabase;

    private FakePrinterTransport $impresora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->impresora = new FakePrinterTransport;
        $this->app->instance(PrinterTransport::class, $this->impresora);
    }

    public function test_el_formato_de_comanda_incluye_datos_items_y_corte(): void
    {
        $pedido = $this->crearPedidoCompleto();

        $payload = (new EscPosFormatter(42))->comanda($pedido);

        $this->assertStringContainsString('COMANDA DE COCINA', $payload);
        $this->assertStringContainsString('Mesa: 4', $payload);
        $this->assertStringContainsString('2x Latte', $payload);
        $this->assertStringContainsString('Sin azucar', $payload);
        $this->assertStringContainsString("\x1D\x56\x00", $payload);
    }

    public function test_el_formato_de_ticket_incluye_totales_y_propina(): void
    {
        $pedido = $this->crearPedidoCompleto();
        $pedido->update([
            'estado' => 'pagado',
            'metodo_pago' => 'efectivo',
            'propina' => 500,
        ]);

        $payload = (new EscPosFormatter(42))->ticket($pedido);

        $this->assertStringContainsString('TOTAL:', $payload);
        $this->assertStringContainsString('$5.500', $payload);
        $this->assertStringContainsString('Pago: Efectivo', $payload);
    }

    public function test_el_pedido_se_imprime_automaticamente_cuando_la_impresora_esta_activa(): void
    {
        $this->activarImpresora();
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [$mesa, $producto] = $this->crearMesaYProducto();

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ]],
        ])->assertCreated()
            ->assertJsonPath('impresion.attempted', true)
            ->assertJsonPath('impresion.ok', true)
            ->assertJsonPath('impresion.fallback', false);

        $this->assertCount(1, $this->impresora->payloads);
        $this->assertStringContainsString('COMANDA DE COCINA', $this->impresora->payloads[0]);
    }

    public function test_si_la_impresora_falla_el_pedido_igual_se_crea_y_queda_el_respaldo(): void
    {
        $this->activarImpresora();
        $this->impresora->falla = true;
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [$mesa, $producto] = $this->crearMesaYProducto();

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ]],
        ])->assertCreated()
            ->assertJsonPath('impresion.ok', false)
            ->assertJsonPath('impresion.fallback', true);

        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_si_la_impresion_esta_desactivada_el_pos_usa_el_navegador(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'barista']));
        [$mesa, $producto] = $this->crearMesaYProducto();

        $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->id,
            'productos' => [[
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ]],
        ])->assertCreated()
            ->assertJsonPath('impresion.attempted', false)
            ->assertJsonPath('impresion.fallback', true);

        $this->assertCount(0, $this->impresora->payloads);
    }

    public function test_el_admin_puede_probar_la_impresora(): void
    {
        $this->activarImpresora();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/impresora/probar')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertCount(1, $this->impresora->payloads);
        $this->assertStringContainsString('PRUEBA DE IMPRESORA', $this->impresora->payloads[0]);
    }

    public function test_un_barista_no_puede_acceder_a_la_configuracion_de_impresora(): void
    {
        $barista = User::factory()->create(['role' => 'barista']);

        $this->actingAs($barista)->get('/admin/impresora')->assertForbidden();
        $this->actingAs($barista)->post('/admin/impresora/probar')->assertForbidden();
    }

    private function activarImpresora(): void
    {
        config([
            'printing.enabled' => true,
            'printing.auto_print' => true,
            'printing.host' => '192.168.1.50',
            'printing.port' => 9100,
            'printing.timeout' => 1,
            'printing.width' => 42,
        ]);
    }

    /**
     * @return array{0: Mesa, 1: Producto}
     */
    private function crearMesaYProducto(): array
    {
        $mesa = Mesa::create(['numero' => '4', 'capacidad' => 4, 'estado' => 'disponible']);
        $producto = Producto::create([
            'nombre' => 'Latte',
            'precio' => '2500.00',
            'categoria' => 'coffee',
            'disponible' => true,
        ]);

        return [$mesa, $producto];
    }

    private function crearPedidoCompleto(): Pedido
    {
        [$mesa, $producto] = $this->crearMesaYProducto();

        $pedido = Pedido::create([
            'mesa_id' => $mesa->id,
            'estado' => 'pendiente',
            'total' => 5000,
        ]);
        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precio,
            'notas_especiales' => 'Sin azúcar',
        ]);

        return $pedido;
    }
}

class FakePrinterTransport implements PrinterTransport
{
    /** @var list<string> */
    public array $payloads = [];

    public bool $falla = false;

    public function send(string $payload): void
    {
        if ($this->falla) {
            throw new RuntimeException('Impresora fuera de línea');
        }

        $this->payloads[] = $payload;
    }

    public function isReachable(): bool
    {
        return ! $this->falla;
    }
}
