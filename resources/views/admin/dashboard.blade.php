@extends('layouts.admin')

@section('content')
        <h2 class="text-2xl font-black text-gray-800 mb-6">Portal de Accesos Rápido</h2>
        
        <!-- Accesos Rápido a las 3 Vistas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- POS -->
            <a href="/pos" target="_blank" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group block">
                <div class="w-14 h-14 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-2xl mb-4 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                    🧑‍🍳
                </div>
                <h3 class="font-black text-lg text-gray-800 mb-1">Punto de Venta (POS)</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Ingresar pedidos, seleccionar mesas e interactuar con los clientes.</p>
            </a>
            
            <!-- KDS -->
            <a href="/kds" target="_blank" class="bg-[#111318] p-6 rounded-3xl border border-gray-800 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group block relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-terracotta/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl mb-4 group-hover:bg-terracotta transition-colors">
                        🔥
                    </div>
                    <h3 class="font-black text-lg text-white mb-1">Cocina (KDS)</h3>
                    <p class="text-white/50 text-sm leading-relaxed">Ver comandas en tiempo real, marcar como preparados o listos.</p>
                </div>
            </a>
            
            <!-- Carta -->
            <a href="/carta" target="_blank" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group block">
                <div class="w-14 h-14 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-2xl mb-4 group-hover:bg-green-500 group-hover:text-white transition-colors">
                    📱
                </div>
                <h3 class="font-black text-lg text-gray-800 mb-1">Carta Digital</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Ver el menú optimizado para celulares que escanean tus clientes.</p>
            </a>

            <!-- Cierre de Turno / Reporte Z -->
            <a href="{{ route('admin.cierre-turno') }}" class="bg-terracotta/10 text-terracotta border border-terracotta/20 p-6 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group block">
                <div class="w-14 h-14 bg-terracotta text-white rounded-2xl flex items-center justify-center text-2xl mb-4 group-hover:bg-terracotta-dark group-hover:text-white transition-colors">
                    💰
                </div>
                <h3 class="font-black text-lg text-gray-800 mb-1">Cierre de Turno (Z)</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Reporte de ventas del día por método de pago para cuadrar caja.</p>
            </a>

            <!-- Inventario -->
            <a href="{{ route('admin.inventario') }}" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group block">
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl mb-4 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                    📦
                </div>
                <h3 class="font-black text-lg text-gray-800 mb-1">Inventario</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Stock, alertas de mínimos y movimientos de productos.</p>
            </a>

            <!-- Auditoría -->
            <a href="{{ route('admin.auditoria') }}" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group block">
                <div class="w-14 h-14 bg-indigo-50 text-indigo-500 rounded-2xl flex items-center justify-center text-2xl mb-4 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                    📋
                </div>
                <h3 class="font-black text-lg text-gray-800 mb-1">Auditoría</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Historial de acciones sensibles: pedidos, cobros, productos e inventario.</p>
            </a>

            <!-- Impresora -->
            <a href="{{ route('admin.impresora') }}" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group block">
                <div class="w-14 h-14 bg-slate-100 text-slate-600 rounded-2xl flex items-center justify-center text-2xl mb-4 group-hover:bg-slate-700 group-hover:text-white transition-colors">
                    🖨️
                </div>
                <h3 class="font-black text-lg text-gray-800 mb-1">Impresora</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Estado y prueba de la térmica de red para comandas y tickets.</p>
            </a>
        </div>

        @if($productosStockBajo > 0)
        <a href="{{ route('admin.inventario') }}" class="flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-2xl mb-6 hover:bg-amber-100 transition-colors">
            <span class="text-2xl">⚠️</span>
            <div>
                <p class="font-bold">Hay {{ $productosStockBajo }} producto(s) en o bajo el stock mínimo.</p>
                <p class="text-sm text-amber-700">Revisa el inventario para reponer antes de que se agoten.</p>
            </div>
        </a>
        @endif

        <h2 class="text-2xl font-black text-gray-800 mb-6">Resumen de Hoy</h2>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-3 h-3 rounded-full bg-green-400"></span>
                    <h6 class="text-gray-400 font-bold text-xs uppercase tracking-wider">Ventas</h6>
                </div>
                <h3 class="text-3xl font-black text-gray-800">${{ number_format($ventasHoy, 0, ',', '.') }}</h3>
            </div>
            
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-3 h-3 rounded-full bg-blue-400"></span>
                    <h6 class="text-gray-400 font-bold text-xs uppercase tracking-wider">Pedidos Hoy</h6>
                </div>
                <h3 class="text-3xl font-black text-gray-800">{{ $pedidosHoy }}</h3>
            </div>
            
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                    <h6 class="text-gray-400 font-bold text-xs uppercase tracking-wider">Mesas Ocupadas</h6>
                </div>
                <h3 class="text-3xl font-black text-gray-800">{{ $mesasActivas }}</h3>
            </div>
            
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-center">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-3 h-3 rounded-full bg-terracotta"></span>
                    <h6 class="text-gray-400 font-bold text-xs uppercase tracking-wider">Productos Activos</h6>
                </div>
                <h3 class="text-3xl font-black text-gray-800">{{ $productosTotales }}</h3>
            </div>
        </div>

        <!-- QR Code -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col lg:flex-row">
            <div class="p-8 lg:w-1/2 flex flex-col justify-center border-b lg:border-b-0 lg:border-r border-gray-100">
                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center text-2xl mb-4">📸</div>
                <h3 class="text-xl font-black text-gray-800 mb-2">Código QR para Mesas</h3>
                <p class="text-gray-500 leading-relaxed mb-6">Imprime este código QR y colócalo en tus mesas. Tus clientes podrán escanearlo con la cámara de sus celulares para ver la carta digital en tiempo real.</p>
                <div class="flex gap-4">
                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data={{ urlencode($urlCarta) }}" download="qr-carta.png" target="_blank" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-3 px-6 rounded-xl transition-colors text-sm text-center flex-1">
                        ⬇️ Descargar QR en Alta Calidad
                    </a>
                </div>
            </div>
            <div class="p-8 lg:w-1/2 flex items-center justify-center bg-gray-50">
                <div class="bg-white p-4 rounded-3xl shadow-lg border border-gray-100">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($urlCarta) }}" alt="QR Carta" class="w-48 h-48 rounded-xl">
                </div>
            </div>
        </div>
@endsection
