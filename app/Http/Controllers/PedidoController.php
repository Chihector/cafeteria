<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    private const ESTADOS_ACTIVOS = ['pendiente', 'preparando', 'listo', 'entregado'];

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

            $productos = [];
            $totalEnCentavos = 0;
            foreach ($validated['productos'] as $index => $item) {
                $producto = Producto::query()
                    ->whereKey($item['producto_id'])
                    ->where('disponible', true)
                    ->lockForUpdate()
                    ->first();

                if (! $producto) {
                    throw ValidationException::withMessages([
                        "productos.{$index}.producto_id" => 'El producto no existe o no está disponible.',
                    ]);
                }

                $productos[] = [$item, $producto];
                $precioEnCentavos = (int) round((float) $producto->precio * 100);
                $totalEnCentavos += $precioEnCentavos * $item['cantidad'];
            }

            $pedido = Pedido::create([
                'mesa_id' => $mesa->id,
                'user_id' => auth()->id(),
                'estado' => 'pendiente',
                'total' => $totalEnCentavos / 100,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            foreach ($productos as [$item, $producto]) {
                $pedido->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'notas_especiales' => $item['notas_especiales'] ?? null,
                ]);
            }

            $mesa->update(['estado' => 'ocupada']);

            return $pedido;
        });

        return response()->json([
            'message' => 'Pedido creado exitosamente',
            'pedido' => $pedido->load('detalles'),
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

        $pedido = DB::transaction(function () use ($pedido, $validated) {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            if (! in_array($pedido->estado, self::ESTADOS_ACTIVOS, true)) {
                throw ValidationException::withMessages([
                    'pedido' => 'Este pedido ya no admite cambios de estado.',
                ]);
            }

            $pedido->update(['estado' => $validated['estado']]);

            return $pedido;
        });

        return response()->json(['message' => 'Estado actualizado', 'estado' => $pedido->estado]);
    }

    public function pagar(Request $request, Pedido $pedido)
    {
        $validated = $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
            'propina' => 'nullable|numeric|min:0',
        ]);

        $pedido = DB::transaction(function () use ($validated, $pedido) {
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

            return $pedido;
        });

        return response()->json(['message' => 'Mesa cobrada exitosamente', 'pedido' => $pedido]);
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
