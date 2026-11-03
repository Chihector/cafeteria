<?php

namespace App\Services;

use App\Models\InventarioMovimiento;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    /**
     * Suma unidades al stock del producto y deja el movimiento registrado.
     */
    public function entrada(Producto $producto, int $cantidad, ?string $motivo = null, ?int $userId = null): InventarioMovimiento
    {
        return DB::transaction(function () use ($producto, $cantidad, $motivo, $userId) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $anterior = (int) $producto->stock;
            $nuevo = $anterior + $cantidad;

            $producto->update([
                'controlar_stock' => true,
                'stock' => $nuevo,
            ]);

            return $producto->movimientos()->create([
                'user_id' => $userId ?? auth()->id(),
                'tipo' => 'entrada',
                'cantidad' => $cantidad,
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevo,
                'motivo' => $motivo,
            ]);
        });
    }

    /**
     * Registra el stock con el que nace un producto nuevo.
     */
    public function registrarStockInicial(Producto $producto, int $stock, ?int $userId = null): ?InventarioMovimiento
    {
        if ($stock <= 0) {
            return null;
        }

        return $producto->movimientos()->create([
            'user_id' => $userId ?? auth()->id(),
            'tipo' => 'entrada',
            'cantidad' => $stock,
            'stock_anterior' => 0,
            'stock_nuevo' => $stock,
            'motivo' => 'Stock inicial del producto',
        ]);
    }

    /**
     * Fija el stock a un valor exacto (conteo físico o corrección).
     */
    public function ajustar(Producto $producto, int $nuevoStock, ?string $motivo = null, ?int $userId = null): InventarioMovimiento
    {
        return DB::transaction(function () use ($producto, $nuevoStock, $motivo, $userId) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $anterior = (int) $producto->stock;

            $producto->update([
                'controlar_stock' => true,
                'stock' => $nuevoStock,
            ]);

            return $producto->movimientos()->create([
                'user_id' => $userId ?? auth()->id(),
                'tipo' => 'ajuste',
                'cantidad' => $nuevoStock - $anterior,
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevoStock,
                'motivo' => $motivo,
            ]);
        });
    }

    /**
     * Devuelve unidades al stock al anular un pedido (evento inverso a registrarVenta).
     * Debe llamarse dentro de la transacción de anulación.
     */
    public function registrarDevolucion(Producto $producto, int $cantidad, Pedido $pedido): ?InventarioMovimiento
    {
        if (! $producto->controlar_stock) {
            return null;
        }

        $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
        $anterior = (int) $producto->stock;
        $nuevo = $anterior + $cantidad;

        $producto->update(['stock' => $nuevo]);

        return $producto->movimientos()->create([
            'user_id' => auth()->id(),
            'pedido_id' => $pedido->id,
            'tipo' => 'devolucion',
            'cantidad' => $cantidad,
            'stock_anterior' => $anterior,
            'stock_nuevo' => $nuevo,
            'motivo' => 'Anulación del pedido #'.$pedido->id,
        ]);
    }

    /**
     * Descuenta el stock de una línea de pedido. Debe llamarse dentro de la
     * transacción de creación del pedido, con el producto ya bloqueado.
     */
    public function registrarVenta(Producto $producto, int $cantidad, Pedido $pedido): ?InventarioMovimiento
    {
        if (! $producto->controlar_stock) {
            return null;
        }

        $anterior = (int) $producto->stock;
        $nuevo = $anterior - $cantidad;

        $producto->update(['stock' => $nuevo]);

        return $producto->movimientos()->create([
            'user_id' => auth()->id(),
            'pedido_id' => $pedido->id,
            'tipo' => 'venta',
            'cantidad' => $cantidad,
            'stock_anterior' => $anterior,
            'stock_nuevo' => $nuevo,
            'motivo' => 'Pedido #'.$pedido->id,
        ]);
    }
}
