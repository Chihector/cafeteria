<?php

namespace App\Http\Controllers;

use App\Events\PedidoActualizado;
use App\Events\PedidoCreado;
use App\Models\Auditoria;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\InventarioService;
use App\Services\Printing\PrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    private const ESTADOS_ACTIVOS = ['pendiente', 'preparando', 'listo', 'entregado'];

    public function __construct(
        private readonly InventarioService $inventario,
        private readonly PrinterService $impresora,
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mesa_id' => 'required|exists:mesas,id',
            'observaciones' => 'nullable|string',
            'productos' => 'required|array',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.notas_especiales' => 'nullable|string',
        ]);

        $pedido = DB::transaction(function () use ($validated) {
            $mesa = Mesa::query()->lockForUpdate()->findOrFail($validated['mesa_id']);
            if ($mesa->estado !== 'disponible' || $mesa->pedidos()->whereIn('estado', self::ESTADOS_ACTIVOS)->exists()) {
                throw ValidationException::withMessages([
                    'mesa_id' => 'La mesa seleccionada no está disponible.',
                ]);
            }

            // Agrupa cantidades para validar el stock una sola vez por producto,
            // incluso si el mismo producto viene en varias líneas del carrito.
            $cantidades = [];
            foreach ($validated['productos'] as $item) {
                $id = (int) $item['producto_id'];
                $cantidades[$id] = ($cantidades[$id] ?? 0) + (int) $item['cantidad'];
            }

            $productos = Producto::query()
                ->whereIn('id', array_keys($cantidades))
                ->where('disponible', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($validated['productos'] as $index => $item) {
                if (! $productos->has((int) $item['producto_id'])) {
                    throw ValidationException::withMessages([
                        "productos.{$index}.producto_id" => 'El producto no existe o no está disponible.',
                    ]);
                }
            }

            foreach ($cantidades as $productoId => $cantidad) {
                $producto = $productos->get($productoId);
                if ($producto->controlar_stock && $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'productos' => "Stock insuficiente para {$producto->nombre}: disponible {$producto->stock}, solicitado {$cantidad}.",
                    ]);
                }
            }

            $totalEnCentavos = 0;
            foreach ($validated['productos'] as $item) {
                $producto = $productos->get((int) $item['producto_id']);
                $precioEnCentavos = (int) round((float) $producto->precio * 100);
                $totalEnCentavos += $precioEnCentavos * (int) $item['cantidad'];
            }

            $pedido = Pedido::create([
                'mesa_id' => $mesa->id,
                'user_id' => auth()->id(),
                'estado' => 'pendiente',
                'total' => $totalEnCentavos / 100,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Inserción en lote para evitar un INSERT por línea de pedido.
            $ahora = now();
            $lineas = [];
            foreach ($validated['productos'] as $item) {
                $producto = $productos->get((int) $item['producto_id']);
                $lineas[] = [
                    'pedido_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'notas_especiales' => $item['notas_especiales'] ?? null,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
            $pedido->detalles()->insert($lineas);

            foreach ($cantidades as $productoId => $cantidad) {
                $this->inventario->registrarVenta($productos->get($productoId), $cantidad, $pedido);
            }

            $mesa->update(['estado' => 'ocupada']);

            return $pedido;
        });

        // Aviso en tiempo real a cocina y al resto de los POS.
        // Precarga mesa para que el broadcast no dispare una consulta extra.
        $pedido->load('mesa');
        PedidoCreado::dispatch($pedido);

        $pedido->load('detalles.producto', 'mesa', 'garzon');

        Auditoria::registrar(
            'crear',
            'pedidos',
            "Pedido #{$pedido->id} creado para mesa ".($pedido->mesa->numero ?? 'N/A'),
            $pedido,
            ['total' => $pedido->total, 'estado' => $pedido->estado],
        );

        // Impresión física directa (si está configurada); si falla, el POS
        // recibe fallback=true y abre la comanda en el navegador.
        $impresion = $this->impresora->comandaAutomatica($pedido);

        Log::info('Pedido creado', [
            'pedido_id' => $pedido->id,
            'mesa_id' => $pedido->mesa_id,
            'user_id' => $pedido->user_id,
            'total' => $pedido->total,
        ]);

        return response()->json([
            'message' => 'Pedido creado exitosamente',
            'pedido' => $pedido,
            'impresion' => $impresion->toArray(),
        ], 201);
    }

    public function getPedidosCocina(Request $request)
    {
        $estado = $request->input('estado');
        $page = $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        $query = Pedido::with(['mesa', 'detalles.producto']);

        if ($estado) {
            $query->where('estado', $estado);
        } else {
            $query->whereIn('estado', self::ESTADOS_ACTIVOS);
        }

        $pedidos = $query->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($pedidos);
    }

    public function updateEstado(Request $request, Pedido $pedido)
    {
        $validated = $request->validate([
            'estado' => 'required|in:pendiente,preparando,listo,entregado',
        ]);

        [$pedido, $estadoAnterior] = DB::transaction(function () use ($pedido, $validated) {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            if (! in_array($pedido->estado, self::ESTADOS_ACTIVOS, true)) {
                throw ValidationException::withMessages([
                    'pedido' => 'Este pedido ya no admite cambios de estado.',
                ]);
            }

            $anterior = $pedido->estado;
            $pedido->update(['estado' => $validated['estado']]);

            return [$pedido, $anterior];
        });

        // Mantiene sincronizados el KDS y el POS de otros dispositivos.
        PedidoActualizado::dispatch($pedido->id, $pedido->mesa_id, $pedido->estado, 'ocupada');

        Auditoria::registrar(
            'actualizar_estado',
            'pedidos',
            "Pedido #{$pedido->id}: {$estadoAnterior} → {$pedido->estado}",
            $pedido,
            ['estado_anterior' => $estadoAnterior, 'estado_nuevo' => $pedido->estado],
        );

        return response()->json(['message' => 'Estado actualizado', 'estado' => $pedido->estado]);
    }

    /**
     * Anula un pedido activo (pendiente/preparando/listo/entregado):
     * repone stock, libera la mesa si no quedan pedidos activos,
     * registra auditoría y avisa a POS/KDS en tiempo real.
     */
    public function anular(Request $request, Pedido $pedido)
    {
        $validated = $request->validate([
            'motivo' => 'required|string|min:5|max:500',
        ]);

        [$pedido, $mesaEstado, $estadoAnterior] = DB::transaction(function () use ($pedido) {
            $pedido = Pedido::query()->lockForUpdate()->with('detalles.producto')->findOrFail($pedido->id);
            if (! in_array($pedido->estado, self::ESTADOS_ACTIVOS, true)) {
                throw ValidationException::withMessages([
                    'pedido' => 'Este pedido ya no se puede anular (está pagado o ya fue anulado).',
                ]);
            }

            foreach ($pedido->detalles as $detalle) {
                $this->inventario->registrarDevolucion($detalle->producto, (int) $detalle->cantidad, $pedido);
            }

            $anterior = $pedido->estado;
            $pedido->update(['estado' => 'anulado']);

            $mesa = Mesa::query()->lockForUpdate()->findOrFail($pedido->mesa_id);
            $tieneOtrosPedidosActivos = $mesa->pedidos()
                ->whereIn('estado', self::ESTADOS_ACTIVOS)
                ->exists();
            $mesa->update(['estado' => $tieneOtrosPedidosActivos ? 'ocupada' : 'disponible']);

            return [$pedido, $mesa->estado, $anterior];
        });

        PedidoActualizado::dispatch($pedido->id, $pedido->mesa_id, $pedido->estado, $mesaEstado);

        Auditoria::registrar(
            'anular',
            'pedidos',
            "Pedido #{$pedido->id} anulado: {$validated['motivo']}",
            $pedido,
            [
                'motivo' => $validated['motivo'],
                'estado_anterior' => $estadoAnterior,
                'total' => $pedido->total,
                'mesa_estado' => $mesaEstado,
            ],
        );

        Log::info('Pedido anulado', [
            'pedido_id' => $pedido->id,
            'mesa_id' => $pedido->mesa_id,
            'user_id' => auth()->id(),
            'motivo' => $validated['motivo'],
            'total' => $pedido->total,
            'mesa_estado' => $mesaEstado,
        ]);

        return response()->json([
            'message' => 'Pedido anulado y stock repuesto.',
            'pedido_id' => $pedido->id,
            'estado' => $pedido->estado,
            'mesa_estado' => $mesaEstado,
        ]);
    }

    public function pagar(Request $request, Pedido $pedido)
    {
        $validated = $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
            'propina' => 'nullable|numeric|min:0',
        ]);

        [$pedido, $mesaEstado] = DB::transaction(function () use ($validated, $pedido) {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            if (! in_array($pedido->estado, self::ESTADOS_ACTIVOS, true)) {
                throw ValidationException::withMessages([
                    'pedido' => 'Este pedido ya no se puede cobrar.',
                ]);
            }

            $mesa = Mesa::query()->lockForUpdate()->findOrFail($pedido->mesa_id);
            $pedido->update([
                'estado' => 'pagado',
                'metodo_pago' => $validated['metodo_pago'],
                'propina' => $validated['propina'] ?? 0,
            ]);

            $tieneOtrosPedidosActivos = $mesa->pedidos()
                ->whereIn('estado', self::ESTADOS_ACTIVOS)
                ->exists();
            $mesa->update(['estado' => $tieneOtrosPedidosActivos ? 'ocupada' : 'disponible']);

            return [$pedido, $mesa->estado];
        });

        // Aviso en tiempo real: el KDS quita la comanda y los POS liberan la mesa.
        PedidoActualizado::dispatch($pedido->id, $pedido->mesa_id, $pedido->estado, $mesaEstado);

        Auditoria::registrar(
            'cobrar',
            'pedidos',
            "Pedido #{$pedido->id} cobrado ({$pedido->metodo_pago})",
            $pedido,
            [
                'total' => $pedido->total,
                'propina' => $pedido->propina,
                'metodo_pago' => $pedido->metodo_pago,
            ],
        );

        Log::info('Pedido cobrado', [
            'pedido_id' => $pedido->id,
            'mesa_id' => $pedido->mesa_id,
            'user_id' => auth()->id(),
            'metodo_pago' => $pedido->metodo_pago,
            'total' => $pedido->total,
            'propina' => $pedido->propina,
            'mesa_estado' => $mesaEstado,
        ]);

        $pedido->load('detalles.producto', 'mesa', 'garzon');
        $impresion = $this->impresora->ticketAutomatico($pedido);

        return response()->json([
            'message' => 'Mesa cobrada exitosamente',
            'pedido' => $pedido,
            'impresion' => $impresion->toArray(),
        ]);
    }

    public function ticket(Pedido $pedido)
    {
        $pedido->load('detalles.producto', 'mesa', 'garzon');

        return view('pos.ticket', compact('pedido'));
    }

    public function comanda(Pedido $pedido)
    {
        $pedido->load('detalles.producto', 'mesa', 'garzon');

        return view('pos.comanda', compact('pedido'));
    }
}
