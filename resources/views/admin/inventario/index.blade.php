@extends('layouts.admin')

@section('title', 'Inventario - Café Rosé')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h2 class="text-3xl font-black text-gray-800 tracking-tight">Inventario</h2>
        <p class="text-gray-500 mt-1">Stock de productos, alertas de mínimos y historial de movimientos.</p>
    </div>
    <a href="{{ route('productos.create') }}" class="bg-terracotta hover:bg-terracotta-dark text-white font-bold py-3 px-6 rounded-2xl shadow-lg shadow-terracotta/30 transition-all">
        Nuevo Producto
    </a>
</div>

@if(session('success'))
<div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 border border-green-100 font-medium">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 border border-red-100 font-medium">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Productos controlados</p>
        <p class="text-3xl font-black text-gray-800">{{ $totalControlados }}</p>
    </div>
    <div class="bg-amber-50 p-6 rounded-3xl border border-amber-100">
        <p class="text-xs text-amber-700 font-bold uppercase tracking-wider mb-2">En o bajo el mínimo</p>
        <p class="text-3xl font-black text-amber-800">{{ $stockBajo->count() }}</p>
    </div>
    <div class="bg-red-50 p-6 rounded-3xl border border-red-100">
        <p class="text-xs text-red-700 font-bold uppercase tracking-wider mb-2">Agotados</p>
        <p class="text-3xl font-black text-red-800">{{ $agotados }}</p>
    </div>
</div>

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8">
    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h3 class="font-black text-lg text-gray-800">Stock por producto</h3>
            <p class="text-sm text-gray-500">Los productos sin control de stock no se descuentan al vender.</p>
        </div>
        <span class="text-xs font-bold text-gray-400 bg-gray-50 border border-gray-100 px-3 py-2 rounded-lg">Entrada suma · Ajuste fija el valor exacto</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
                <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider font-bold">
                    <th class="p-4 border-b border-gray-100">Producto</th>
                    <th class="p-4 border-b border-gray-100 text-center">Controla</th>
                    <th class="p-4 border-b border-gray-100 text-center">Stock</th>
                    <th class="p-4 border-b border-gray-100 text-center">Mínimo</th>
                    <th class="p-4 border-b border-gray-100 text-center">Estado</th>
                    <th class="p-4 border-b border-gray-100">Entrada</th>
                    <th class="p-4 border-b border-gray-100">Ajuste</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productos as $producto)
                <tr class="hover:bg-gray-50/50 transition-colors align-top">
                    <td class="p-4">
                        <p class="font-bold text-gray-800">{{ $producto->nombre }}</p>
                        <p class="text-xs text-gray-400 uppercase">{{ $producto->categoria }}</p>
                    </td>
                    <td class="p-4 text-center">
                        @if($producto->controlar_stock)
                            <span class="text-green-600 font-bold text-sm">Sí</span>
                        @else
                            <span class="text-gray-400 text-sm">No</span>
                        @endif
                    </td>
                    <td class="p-4 text-center">
                        <span class="text-lg font-black {{ $producto->stockBajo() ? 'text-red-600' : 'text-gray-800' }}">{{ $producto->stock }}</span>
                    </td>
                    <td class="p-4 text-center text-gray-500 font-semibold">{{ $producto->stock_minimo }}</td>
                    <td class="p-4 text-center">
                        @if(! $producto->controlar_stock)
                            <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded-lg text-xs font-bold">Sin control</span>
                        @elseif($producto->stock <= 0)
                            <span class="bg-red-50 text-red-600 px-2 py-1 rounded-lg text-xs font-bold">Agotado</span>
                        @elseif($producto->stockBajo())
                            <span class="bg-amber-50 text-amber-600 px-2 py-1 rounded-lg text-xs font-bold">Stock bajo</span>
                        @else
                            <span class="bg-green-50 text-green-600 px-2 py-1 rounded-lg text-xs font-bold">OK</span>
                        @endif
                    </td>
                    <td class="p-4">
                        <form action="{{ route('admin.inventario.entrada', $producto) }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="number" name="cantidad" min="1" value="1" required class="w-16 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-sm">
                            <input type="text" name="motivo" placeholder="Motivo" class="w-28 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">+</button>
                        </form>
                    </td>
                    <td class="p-4">
                        <form action="{{ route('admin.inventario.ajuste', $producto) }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="number" name="stock" min="0" value="{{ $producto->stock }}" required class="w-16 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-sm">
                            <input type="text" name="motivo" placeholder="Motivo" class="w-28 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs">
                            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Fijar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400">No hay productos registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="font-black text-lg text-gray-800">Movimientos recientes</h3>
            <p class="text-sm text-gray-500">Entradas, ajustes y ventas que descuentan stock.</p>
        </div>
        <form method="GET" action="{{ route('admin.inventario') }}" class="flex gap-2">
            <select name="producto_id" class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <option value="">Todos los productos</option>
                @foreach($productos as $producto)
                    <option value="{{ $producto->id }}" @selected($productoId === $producto->id)>{{ $producto->nombre }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-800 text-white text-sm font-bold px-4 py-2 rounded-xl">Filtrar</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
                <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider font-bold">
                    <th class="p-4 border-b border-gray-100">Fecha</th>
                    <th class="p-4 border-b border-gray-100">Producto</th>
                    <th class="p-4 border-b border-gray-100">Tipo</th>
                    <th class="p-4 border-b border-gray-100 text-center">Cantidad</th>
                    <th class="p-4 border-b border-gray-100 text-center">Stock</th>
                    <th class="p-4 border-b border-gray-100">Usuario</th>
                    <th class="p-4 border-b border-gray-100">Motivo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($movimientos as $movimiento)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="p-4 text-sm text-gray-500 whitespace-nowrap">{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                    <td class="p-4 font-semibold text-gray-800">{{ $movimiento->producto->nombre ?? 'Producto eliminado' }}</td>
                    <td class="p-4">
                        <span class="text-xs font-bold px-2 py-1 rounded-lg
                            @if($movimiento->tipo === 'entrada') bg-green-50 text-green-600
                            @elseif($movimiento->tipo === 'venta') bg-blue-50 text-blue-600
                            @else bg-amber-50 text-amber-600 @endif">
                            {{ $movimiento->tipoLabel() }}
                        </span>
                    </td>
                    <td class="p-4 text-center font-bold {{ $movimiento->cantidad < 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $movimiento->cantidad > 0 ? '+' : '' }}{{ $movimiento->cantidad }}
                    </td>
                    <td class="p-4 text-center text-sm text-gray-500">{{ $movimiento->stock_anterior }} → <strong class="text-gray-800">{{ $movimiento->stock_nuevo }}</strong></td>
                    <td class="p-4 text-sm text-gray-500">{{ $movimiento->usuario->name ?? 'Sistema' }}</td>
                    <td class="p-4 text-sm text-gray-500">
                        {{ $movimiento->motivo }}
                        @if($movimiento->pedido_id)
                            <a href="{{ route('pedidos.ticket', $movimiento->pedido_id) }}" target="_blank" class="text-terracotta underline ml-1">Pedido #{{ $movimiento->pedido_id }}</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400">Todavía no hay movimientos de inventario.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($movimientos->hasPages())
    <div class="p-6 border-t border-gray-100">
        {{ $movimientos->links() }}
    </div>
    @endif
</div>
@endsection
