@extends('layouts.admin')

@section('title', 'Nuevo Usuario - Café Rosé')

@section('content')
<div class="mb-8 flex items-center gap-4">
    <a href="{{ route('admin.usuarios.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-3 rounded-xl transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
    </a>
    <div>
        <h2 class="text-3xl font-black text-gray-800 tracking-tight">Crear Usuario</h2>
        <p class="text-gray-500 mt-1">Añade una cuenta para una persona del equipo.</p>
    </div>
</div>

<form action="{{ route('admin.usuarios.store') }}" method="POST" class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm max-w-2xl">
    @csrf

    <div class="space-y-6">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Nombre Completo</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Ej. María González" required>
            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Correo Electrónico</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="usuario@test.com" required>
            @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Rol</label>
            <select name="role" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" required>
                <option value="">Seleccione...</option>
                @foreach($roles as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ old('role') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
            </select>
            @error('role') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Contraseña Inicial</label>
                <input type="password" name="password" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Mínimo 8 caracteres" required>
                @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Repite la contraseña" required>
            </div>
        </div>

        <button type="submit" class="bg-terracotta hover:bg-terracotta-dark text-white font-bold py-4 px-8 rounded-2xl shadow-lg shadow-terracotta/30 transition-all w-full md:w-auto">
            Crear Usuario
        </button>
    </div>
</form>
@endsection
