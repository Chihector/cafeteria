@extends('layouts.admin')

@section('title', 'Nuevo Producto - Café Rosé')

@section('content')
<div class="mb-8 flex items-center gap-4">
    <a href="{{ route('productos.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-3 rounded-xl transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
    </a>
    <div>
        <h2 class="text-3xl font-black text-gray-800 tracking-tight">Crear Nuevo Producto</h2>
        <p class="text-gray-500 mt-1">Añade un nuevo ítem a la carta digital.</p>
    </div>
</div>

<form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
    @csrf
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Columna Izquierda: Datos Básicos -->
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Producto</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Ej. Latte Vainilla" required>
                @error('nombre') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Breve descripción para la carta...">{{ old('descripcion') }}</textarea>
                @error('descripcion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Precio ($)</label>
                    <input type="number" name="precio" value="{{ old('precio') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Ej. 2500" required>
                    @error('precio') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Categoría</label>
                    <select name="categoria" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" required>
                        <option value="">Seleccione...</option>
                        <option value="cafes" {{ old('categoria') == 'cafes' ? 'selected' : '' }}>Cafés</option>
                        <option value="pasteleria" {{ old('categoria') == 'pasteleria' ? 'selected' : '' }}>Pastelería</option>
                        <option value="sandwiches" {{ old('categoria') == 'sandwiches' ? 'selected' : '' }}>Sándwiches</option>
                        <option value="bebidas_frias" {{ old('categoria') == 'bebidas_frias' ? 'selected' : '' }}>Bebidas Frías</option>
                    </select>
                    @error('categoria') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Alérgenos</label>
                <div class="flex flex-wrap gap-3">
                    @php $oldAlergenos = old('alergenos', []); @endphp
                    <label class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="alergenos[]" value="Gluten" class="text-terracotta rounded focus:ring-terracotta" {{ in_array('Gluten', $oldAlergenos) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-gray-700">Gluten</span>
                    </label>
                    <label class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="alergenos[]" value="Lácteos" class="text-terracotta rounded focus:ring-terracotta" {{ in_array('Lácteos', $oldAlergenos) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-gray-700">Lácteos</span>
                    </label>
                    <label class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-2 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                        <input type="checkbox" name="alergenos[]" value="Frutos Secos" class="text-terracotta rounded focus:ring-terracotta" {{ in_array('Frutos Secos', $oldAlergenos) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-gray-700">Frutos Secos</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Configuración y Multimedia -->
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fotografía</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-terracotta hover:bg-terracotta/5 transition-colors group cursor-pointer" onclick="document.getElementById('file-upload').click()">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-terracotta transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600 justify-center">
                            <span class="relative cursor-pointer bg-transparent rounded-md font-medium text-terracotta hover:text-terracotta-dark focus-within:outline-none">
                                <span>Sube un archivo</span>
                                <input id="file-upload" name="imagen" type="file" class="sr-only" accept="image/*">
                            </span>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG hasta 2MB</p>
                    </div>
                </div>
                @error('imagen') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 space-y-4">
                <h4 class="font-bold text-gray-800 border-b border-gray-200 pb-2 mb-4">Opciones Adicionales</h4>
                
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="disponible" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-gray-800">Disponible en Stock</span>
                        <p class="text-xs text-gray-500">Si lo apagas, no saldrá en el POS ni en la carta.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="destacado" value="1" class="sr-only peer" {{ old('destacado') ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-400"></div>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-gray-800">Producto Destacado</span>
                        <p class="text-xs text-gray-500">Aparecerá primero con una estrella en la carta.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="vegetariano" value="1" class="sr-only peer" {{ old('vegetariano') ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-gray-800">Opción Vegetariana</span>
                        <p class="text-xs text-gray-500">Tendrá el icono verde de 🌱 en la carta.</p>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
        <button type="submit" class="bg-terracotta hover:bg-terracotta-dark text-white font-bold py-3 px-8 rounded-2xl shadow-lg shadow-terracotta/30 transition-all flex items-center gap-2">
            Guardar Producto
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
        </button>
    </div>
</form>
@endsection
