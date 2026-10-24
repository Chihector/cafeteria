@extends('layouts.admin')

@section('title', 'Auditoría - Café Rosé')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-black text-gray-800 tracking-tight">Auditoría</h2>
    <p class="text-gray-500 mt-1">Historial de acciones sensibles realizadas en el sistema.</p>
</div>

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-8">
    <form method="GET" action="{{ route('admin.auditoria') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Módulo</label>
            <select name="modulo" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($modulos as $modulo)
                    <option value="{{ $modulo }}" @selected(request('modulo') === $modulo)>{{ ucfirst($modulo) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Acción</label>
            <select name="accion" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <option value="">Todas</option>
                @foreach($acciones as $accion)
                    <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ str_replace('_', ' ', ucfirst($accion)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Buscar</label>
            <div class="flex gap-2">
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Descripción o IP" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <button type="submit" class="bg-gray-800 text-white text-sm font-bold px-4 py-2 rounded-xl">Filtrar</button>
            </div>
        </div>
    </form>
</div>

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
                <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider font-bold">
                    <th class="p-4 border-b border-gray-100">Fecha</th>
                    <th class="p-4 border-b border-gray-100">Usuario</th>
                    <th class="p-4 border-b border-gray-100">Módulo</th>
                    <th class="p-4 border-b border-gray-100">Acción</th>
                    <th class="p-4 border-b border-gray-100">Descripción</th>
                    <th class="p-4 border-b border-gray-100">IP</th>
                    <th class="p-4 border-b border-gray-100">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($auditorias as $auditoria)
                <tr class="hover:bg-gray-50/50 transition-colors align-top">
                    <td class="p-4 text-sm text-gray-500 whitespace-nowrap">{{ $auditoria->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="p-4">
                        <p class="font-semibold text-gray-800 text-sm">{{ $auditoria->usuario->name ?? 'Sistema / invitado' }}</p>
                        <p class="text-xs text-gray-400">{{ $auditoria->usuario->email ?? '' }}</p>
                    </td>
                    <td class="p-4">
                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-lg text-xs font-bold uppercase">{{ $auditoria->modulo }}</span>
                    </td>
                    <td class="p-4">
                        <span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg text-xs font-bold">{{ str_replace('_', ' ', $auditoria->accion) }}</span>
                    </td>
                    <td class="p-4 text-sm text-gray-700 max-w-sm">{{ $auditoria->descripcion }}</td>
                    <td class="p-4 text-xs text-gray-400 font-mono">{{ $auditoria->ip ?? '—' }}</td>
                    <td class="p-4">
                        @if($auditoria->cambios)
                            <details>
                                <summary class="text-xs font-bold text-terracotta cursor-pointer">Ver cambios</summary>
                                <pre class="mt-2 text-[10px] bg-gray-900 text-green-300 p-3 rounded-xl overflow-x-auto max-w-xs">{{ json_encode($auditoria->cambios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @else
                            <span class="text-xs text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400">No hay registros de auditoría con esos filtros.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($auditorias->hasPages())
    <div class="p-6 border-t border-gray-100">
        {{ $auditorias->links() }}
    </div>
    @endif
</div>
@endsection
