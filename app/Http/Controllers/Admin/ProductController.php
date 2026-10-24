<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(private readonly InventarioService $inventario) {}

    public function index()
    {
        $productos = Producto::orderBy('id', 'desc')->get();

        return view('admin.productos.index', compact('productos'));
    }

    public function create()
    {
        return view('admin.productos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'categoria' => 'required|string',
            'imagen' => 'nullable|image|max:2048',
            'alergenos' => 'nullable|array',
            'controlar_stock' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
        ]);

        $validated['disponible'] = $request->has('disponible');
        $validated['destacado'] = $request->has('destacado');
        $validated['vegetariano'] = $request->has('vegetariano');
        $validated['controlar_stock'] = $request->boolean('controlar_stock');
        $validated['stock'] = (int) $request->input('stock', 0);
        $validated['stock_minimo'] = (int) $request->input('stock_minimo', 0);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto = Producto::create($validated);

        if ($producto->controlar_stock && $producto->stock > 0) {
            $this->inventario->registrarStockInicial($producto, $producto->stock);
        }

        Auditoria::registrar(
            'crear',
            'productos',
            "Producto creado: {$producto->nombre}",
            $producto,
            $producto->only(['nombre', 'precio', 'categoria', 'controlar_stock', 'stock', 'stock_minimo']),
        );

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Producto $producto)
    {
        return view('admin.productos.edit', compact('producto'));
    }

    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'categoria' => 'required|string',
            'imagen' => 'nullable|image|max:2048',
            'alergenos' => 'nullable|array',
            'controlar_stock' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
        ]);

        $stockAnterior = (int) $producto->stock;
        $controlAnterior = (bool) $producto->controlar_stock;

        $validated['disponible'] = $request->has('disponible');
        $validated['destacado'] = $request->has('destacado');
        $validated['vegetariano'] = $request->has('vegetariano');
        $validated['controlar_stock'] = $request->boolean('controlar_stock');
        $validated['stock'] = (int) $request->input('stock', 0);
        $validated['stock_minimo'] = (int) $request->input('stock_minimo', 0);

        // Si la vista no envía alérgenos, se borran (Laravel no manda el array si están todos desmarcados).
        if (! $request->has('alergenos')) {
            $validated['alergenos'] = [];
        }

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($validated);

        if ((int) $producto->stock !== $stockAnterior) {
            $producto->movimientos()->create([
                'user_id' => auth()->id(),
                'tipo' => 'ajuste',
                'cantidad' => (int) $producto->stock - $stockAnterior,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => (int) $producto->stock,
                'motivo' => $controlAnterior
                    ? 'Actualización desde la ficha del producto'
                    : 'Control de stock activado desde la ficha del producto',
            ]);
        }

        Auditoria::registrar(
            'actualizar',
            'productos',
            "Producto actualizado: {$producto->nombre}",
            $producto,
            $producto->getChanges(),
        );

        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto)
    {
        // Si el producto ya forma parte de pedidos históricos, borrarlo
        // arrastraría esas líneas (FK cascade) y descuadraría reportes pasados.
        if ($producto->detalles()->exists()) {
            return redirect()
                ->route('productos.index')
                ->with('error', 'No se puede eliminar un producto con pedidos registrados. Desactívalo para ocultarlo del POS y la carta.');
        }

        // Los movimientos de inventario también son historial: se conservan.
        if ($producto->movimientos()->exists()) {
            return redirect()
                ->route('productos.index')
                ->with('error', 'No se puede eliminar un producto con movimientos de inventario. Desactívalo en su lugar.');
        }

        $nombre = $producto->nombre;

        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        Auditoria::registrar(
            'eliminar',
            'productos',
            "Producto eliminado: {$nombre}",
            $producto,
            ['nombre' => $nombre],
        );

        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
