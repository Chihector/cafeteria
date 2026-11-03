@extends('layouts.admin')

@section('title', 'Editar Usuario - Café Rosé')

@section('content')
<div class="mb-8 flex items-center gap-4">
    <a href="{{ route('admin.usuarios.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-3 rounded-xl transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
    </a>
    <div>
        <h2 class="text-3xl font-black text-gray-800 tracking-tight">Editar Usuario</h2>
        <p class="text-gray-500 mt-1">{{ $usuario->name }} · {{ $usuario->email }}</p>
    </div>
</div>

@if(session('error'))
<div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 border border-red-100 font-medium">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST" class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
        @csrf
        @method('PUT')

        <h3 class="font-black text-lg text-gray-800 border-b border-gray-100 pb-3 mb-6">Datos de la cuenta</h3>

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nombre Completo</label>
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" required>
                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Correo Electrónico</label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" required>
                @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Rol</label>
                <select name="role" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" required>
                    @foreach($roles as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ old('role', $usuario->role) === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('role') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                @if($usuario->id === auth()->id())
                    <p class="text-xs text-amber-600 font-medium mt-1">No puedes cambiar tu propio rol.</p>
                @endif
            </div>

            <button type="submit" class="bg-terracotta hover:bg-terracotta-dark text-white font-bold py-4 px-8 rounded-2xl shadow-lg shadow-terracotta/30 transition-all w-full">
                Guardar Cambios
            </button>
        </div>
    </form>

    <div class="space-y-6">
        <form action="{{ route('admin.usuarios.reset-password', $usuario) }}" method="POST" class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
            @csrf

            <h3 class="font-black text-lg text-gray-800 border-b border-gray-100 pb-3 mb-6">Restablecer contraseña</h3>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nueva Contraseña</label>
                    <input type="password" name="password" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Mínimo 8 caracteres" required>
                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmation" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-terracotta/50 focus:border-terracotta transition-all" placeholder="Repite la contraseña" required>
                </div>

                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-4 px-8 rounded-2xl transition-all w-full">
                    Restablecer Contraseña
                </button>
            </div>
        </form>

        <div class="bg-white p-8 rounded-3xl border {{ $usuario->activo ? 'border-gray-100' : 'border-red-100 bg-red-50/30' }} shadow-sm">
            <h3 class="font-black text-lg text-gray-800 border-b border-gray-100 pb-3 mb-4">Estado de la cuenta</h3>
            <p class="text-sm text-gray-600 mb-4">
                @if($usuario->activo)
                    Esta cuenta puede iniciar sesión normalmente.
                @else
                    <strong class="text-red-600">Esta cuenta está desactivada:</strong> no puede iniciar sesión hasta que se reactive.
                @endif
            </p>
            <form action="{{ route('admin.usuarios.toggle', $usuario) }}" method="POST" onsubmit="return confirm('{{ $usuario->activo ? '¿Desactivar esta cuenta? No podrá iniciar sesión.' : '¿Reactivar esta cuenta?' }}');">
                @csrf
                <button type="submit" class="px-6 py-3 rounded-2xl font-bold transition-all {{ $usuario->activo ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700' }} w-full">
                    {{ $usuario->activo ? 'Desactivar cuenta' : 'Reactivar cuenta' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
