@extends('layouts.admin')

@section('title', 'Usuarios - Café Rosé')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h2 class="text-3xl font-black text-gray-800 tracking-tight">Usuarios</h2>
        <p class="text-gray-500 mt-1">Gestión de cuentas: creación, edición, activación y contraseñas.</p>
    </div>
    <a href="{{ route('admin.usuarios.create') }}" class="bg-terracotta hover:bg-terracotta-dark text-white font-bold py-3 px-6 rounded-2xl shadow-lg shadow-terracotta/30 transition-all">
        Nuevo Usuario
    </a>
</div>

@if(session('success'))
<div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 border border-green-100 font-medium">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 border border-red-100 font-medium">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-black text-lg text-gray-800">Cuentas registradas</h3>
            <p class="text-sm text-gray-500">Administradores activos: <strong>{{ $totalAdmins }}</strong></p>
        </div>
        <span class="text-xs font-bold text-gray-400 bg-gray-50 border border-gray-100 px-3 py-2 rounded-lg">No se eliminan cuentas para conservar el historial</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
                <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider font-bold">
                    <th class="p-4 border-b border-gray-100">Usuario</th>
                    <th class="p-4 border-b border-gray-100">Rol</th>
                    <th class="p-4 border-b border-gray-100 text-center">Estado</th>
                    <th class="p-4 border-b border-gray-100 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $usuario)
                    <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-terracotta/10 text-terracotta flex items-center justify-center font-black text-sm">
                                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 leading-tight">{{ $usuario->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $usuario->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $usuario->role === 'admin' ? 'bg-purple-50 text-purple-600 border border-purple-100' : ($usuario->role === 'barista' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-sky-50 text-sky-600 border border-sky-100') }}">
                                {{ $usuario->roleLabel() }}
                            </span>
                            @if($usuario->id === auth()->id())
                                <span class="ml-2 text-[10px] font-bold text-gray-400 uppercase">(tú)</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @if($usuario->activo)
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-600 border border-green-100">Activo</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-500 border border-red-100">Desactivado</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="px-3 py-2 text-xs font-bold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Editar</a>
                                <form action="{{ route('admin.usuarios.toggle', $usuario) }}" method="POST" onsubmit="return confirm('{{ $usuario->activo ? '¿Desactivar esta cuenta? No podrá iniciar sesión.' : '¿Reactivar esta cuenta?' }}');">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 text-xs font-bold rounded-lg transition-colors {{ $usuario->activo ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100' }}">
                                        {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
