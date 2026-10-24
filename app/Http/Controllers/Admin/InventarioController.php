<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventario) {}

    public function index(Request $request)
    {
        $productos = Producto::query()->orderBy('nombre')->get();
        $productoId = $request->integer('producto_id') ?: null;

        $movimientos = InventarioMovimiento::query()
            ->with(['producto', 'usuario', 'pedido'])
            ->when($productoId, fn ($query) => $query->where('producto_id', $productoId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventario.index', [
            'productos' => $productos,
            'movimientos' => $movimientos,
            'productoId' => $productoId,
            'totalControlados' => $productos->where('controlar_stock', true)->count(),
            'stockBajo' => $productos->filter->stockBajo(),
            'agotados' => $productos->where('controlar_stock', true)->where('stock', '<=', 0)->count(),
        ]);
    }

    public function entrada(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:255',
        ]);

        $movimiento = $this->inventario->entrada(
            $producto,
            (int) $validated['cantidad'],
            $validated['motivo'] ?? 'Entrada manual de inventario',
        );

        Auditoria::registrar(
            'entrada',
            'inventario',
            "Entrada de {$movimiento->cantidad} unidades de {$producto->nombre}",
            $producto,
            [
                'stock_anterior' => $movimiento->stock_anterior,
                'stock_nuevo' => $movimiento->stock_nuevo,
                'motivo' => $movimiento->motivo,
            ],
        );

        return back()->with('success', "Stock actualizado: {$producto->nombre} ahora tiene {$movimiento->stock_nuevo} unidades.");
    }

    public function ajuste(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'motivo' => 'nullable|string|max:255',
        ]);

        $movimiento = $this->inventario->ajustar(
            $producto,
            (int) $validated['stock'],
            $validated['motivo'] ?? 'Ajuste manual de inventario',
        );

        Auditoria::registrar(
            'ajuste',
            'inventario',
            "Ajuste de stock de {$producto->nombre}: {$movimiento->stock_anterior} → {$movimiento->stock_nuevo}",
            $producto,
            [
                'stock_anterior' => $movimiento->stock_anterior,
                'stock_nuevo' => $movimiento->stock_nuevo,
                'motivo' => $movimiento->motivo,
            ],
        );

        return back()->with('success', "Stock ajustado: {$producto->nombre} quedó con {$movimiento->stock_nuevo} unidades.");
    }
}
