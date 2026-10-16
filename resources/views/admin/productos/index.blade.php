@extends('layouts.admin')

@section('title', 'Productos - Café Rosé')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h2 class="text-3xl font-black text-gray-800 tracking-tight">Gestión de Productos</h2>
        <p class="text-gray-500 mt-1">Administra el menú de tu cafetería. Los cambios se reflejarán en la Carta Digital y el POS.</p>
    </div>
    <a href="{{ route('productos.create') }}" class="bg-terracotta hover:bg-terracotta-dark text-white font-bold py-3 px-6 rounded-2xl shadow-lg shadow-terracotta/30 transition-all flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Nuevo Producto
    </a>
</div>

@if(session('success'))
<div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 border border-green-100 flex items-center gap-3">
    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
    <span class="font-medium">{{ session('success') }}</span>
</div>
@endif

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider font-bold">
                    <th class="p-4 border-b border-gray-100">Producto</th>
                    <th class="p-4 border-b border-gray-100">Categoría</th>
                    <th class="p-4 border-b border-gray-100">Precio</th>
                    <th class="p-4 border-b border-gray-100 text-center">Estado</th>
                    <th class="p-4 border-b border-gray-100 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productos as $producto)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-4">
                            @if($producto->imagen)
                                <img src="{{ Storage::url($producto->imagen) }}" alt="{{ $producto->nombre }}" class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                            @else
                                <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-300 text-xl border border-gray-100">☕</div>
                            @endif
                            <div>
                                <h3 class="font-bold text-gray-800">{{ $producto->nombre }}</h3>
                                <p class="text-xs text-gray-400 max-w-xs truncate">{{ $producto->descripcion }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-lg text-xs font-bold uppercase">{{ $producto->categoria }}</span>
                    </td>
                    <td class="p-4 font-bold text-gray-800">
                        ${{ number_format($producto->precio, 0, ',', '.') }}
                    </td>
                    <td class="p-4 text-center">
                        @if($producto->disponible)
                            <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-600 px-3 py-1 rounded-lg text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activo
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-600 px-3 py-1 rounded-lg text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactivo
                            </span>
                        @endif
                    </td>
                    <td class="p-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('productos.edit', $producto) }}" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 p-2 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </a>
                            <form action="{{ route('productos.destroy', $producto) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este producto?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-gray-400">
                        No hay productos registrados. <a href="{{ route('productos.create') }}" class="text-terracotta underline">Crea el primero</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
