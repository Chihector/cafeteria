@extends('layouts.admin')

@section('title', 'Impresora - Café Rosé')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-black text-gray-800 tracking-tight">Impresora térmica de red</h2>
    <p class="text-gray-500 mt-1">Comandas y tickets se envían directo por ESC/POS al puerto de la impresora (Fase 13).</p>
</div>

@if(session('success'))
<div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 border border-green-100 font-medium">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 border border-red-100 font-medium">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Estado</p>
        @if($habilitada)
            <p class="text-2xl font-black text-green-600">Habilitada</p>
        @else
            <p class="text-2xl font-black text-gray-400">Desactivada</p>
        @endif
        <p class="text-xs text-gray-500 mt-2">PRINTER_ENABLED={{ $habilitada ? 'true' : 'false' }}</p>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Impresora</p>
        <p class="text-2xl font-black text-gray-800">{{ $host ?: 'Sin configurar' }}</p>
        <p class="text-xs text-gray-500 mt-2">Puerto {{ $puerto }} · Ancho {{ $ancho }} columnas</p>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Impresión automática</p>
        <p class="text-2xl font-black {{ $autoPrint ? 'text-green-600' : 'text-amber-600' }}">{{ $autoPrint ? 'Sí' : 'No' }}</p>
        <p class="text-xs text-gray-500 mt-2">Al crear pedido y al cobrar. Timeout: {{ $timeout }} s</p>
    </div>
</div>

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 md:p-8 mb-8">
    <h3 class="font-black text-lg text-gray-800 mb-4">Pruebas</h3>
    <div class="flex flex-wrap gap-3">
        <form action="{{ route('admin.impresora.comprobar') }}" method="POST">
            @csrf
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-6 rounded-2xl transition-colors">
                Comprobar conexión
            </button>
        </form>
        <form action="{{ route('admin.impresora.probar') }}" method="POST">
            @csrf
            <button type="submit" class="bg-terracotta hover:bg-terracotta-dark text-white font-bold py-3 px-6 rounded-2xl shadow-lg shadow-terracotta/30 transition-colors">
                Imprimir prueba
            </button>
        </form>
    </div>
    <p class="text-sm text-gray-500 mt-4">La prueba imprime un texto corto. Si falla, el POS seguirá usando la impresión por navegador como respaldo.</p>
</div>

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 md:p-8">
    <h3 class="font-black text-lg text-gray-800 mb-4">Configuración en <code class="bg-gray-100 px-2 py-1 rounded text-sm">.env</code></h3>
    <pre class="bg-gray-900 text-green-300 p-5 rounded-2xl text-sm overflow-x-auto"># Activar impresión directa
PRINTER_ENABLED=true
PRINTER_AUTO_PRINT=true

# IP de la impresora en la red local (ej. Epson TM-T20 / Xprinter XP-80C)
PRINTER_HOST=192.168.1.50
PRINTER_PORT=9100
PRINTER_TIMEOUT=3

# 42 columnas para 80 mm; usa 32 para 58 mm
PRINTER_WIDTH=42</pre>
    <div class="mt-5 text-sm text-gray-500 space-y-2">
        <p>• La impresora debe estar encendida, conectada a la misma red y aceptar ESC/POS por el puerto 9100.</p>
        <p>• El servidor Laravel (no el navegador) es quien se conecta a la impresora.</p>
        <p>• Después de cambiar el <code class="bg-gray-100 px-1 rounded">.env</code>, ejecuta <code class="bg-gray-100 px-1 rounded">php artisan config:clear</code>.</p>
        <p>• Si falla la impresión directa, la comanda/ticket se abre en el navegador para imprimir manualmente.</p>
    </div>
</div>
@endsection
