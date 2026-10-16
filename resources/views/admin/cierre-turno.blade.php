@extends('layouts.admin')

@php
    $periodoLabels = [
        'dia'  => 'Día',
        'mes'  => 'Mes',
        'anio' => 'Año',
    ];
@endphp

@section('content')
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black text-gray-800">Cierre de Turno <span class="text-terracotta">Reporte Z</span></h2>
                <p class="text-gray-500 text-sm">Resumen y comparación de ventas por día, mes o año. Ideal para cuadrar caja y llevar control histórico.</p>
            </div>
            <a href="/admin" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2.5 px-6 rounded-xl transition-colors text-sm">
                ← Volver al Panel
            </a>
        </div>
    </div>

    <!-- Filtros de período -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm mb-8">
        <form method="GET" action="{{ route('admin.cierre-turno') }}" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex items-center gap-3">
                <span class="text-gray-500 text-sm font-medium">Período:</span>
                <select name="periodo" id="periodo" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-terracotta/30 focus:border-terracotta outline-none bg-gray-50">
                    @foreach ($periodoLabels as $key => $label)
                        <option value="{{ $key }}" {{ $periodo === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-gray-500 text-sm font-medium">Valor:</span>
                <input type="date" name="valor" id="valor" value="{{ $valor }}" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-terracotta/30 focus:border-terracotta outline-none bg-gray-50">
            </div>

            <div class="flex gap-2">
                <button class="bg-terracotta text-white font-bold py-2.5 px-6 rounded-xl hover:bg-terracotta-dark transition-all shadow-lg shadow-terracotta/20 text-sm">
                    🔍 Generar Reporte
                </button>
                <button id="btnPdf" class="bg-green-500 text-white font-bold py-2.5 px-6 rounded-xl hover:bg-green-600 transition-all shadow-lg shadow-green-500/30 text-sm" @click="descargarPDF(event)">
                    📄 Descargar PDF
                </button>
            </div>
        </form>

        <p class="text-xs text-gray-400 mt-3">Seleccioná día, mes o año. El reporte se recalcula automáticamente para el rango elegido.</p>
    </div>

    @if ($cantidadPedidos === 0)
        <!-- Estado vacío -->
        <div class="bg-white p-10 rounded-3xl border border-gray-100 shadow-sm text-center mb-8">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center text-4xl mx-auto mb-4">📄</div>
            <h3 class="text-xl font-black text-gray-800 mb-2">Sin pedidos pagados</h3>
            <p class="text-gray-500 text-sm max-w-xs mx-auto">No hay pedidos con estado "pagado" para el {{ $periodoLabels[$periodo] }} {{ $etiqueta }}.</p>
        </div>
    @else
        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-3 h-3 rounded-full bg-green-400"></span>
                    <h6 class="text-gray-400 font-bold text-xs uppercase tracking-wider">Ventas Netas</h6>
                </div>
                <h3 class="text-2xl font-black text-gray-800">${{ number_format($totalVentas, 0, ',', '.') }}</h3>
                <p class="text-xs text-gray-400 mt-1">Suma de totales de pedidos</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                    <h6 class="text-gray-400 font-bold text-xs uppercase tracking-wider">Propinas</h6>
                </div>
                <h3 class="text-2xl font-black text-gray-800">${{ number_format($totalPropinas, 0, ',', '.') }}</h3>
                <p class="text-xs text-gray-400 mt-1">Propinas sugeridas (10%)</p>
            </div>

            <div class="bg-terracotta text-white p-6 rounded-3xl shadow-lg shadow-terracotta/20 flex flex-col">
                <div class="flex items-center gap-3 mb-3 opacity-90">
                    <span class="w-3 h-3 rounded-full bg-white/30"></span>
                    <h6 class="text-white/80 font-bold text-xs uppercase tracking-wider">Total a Cuadrar</h6>
                </div>
                <h3 class="text-3xl font-black text-white">${{ number_format($totalGeneral, 0, ',', '.') }}</h3>
                <p class="text-xs text-white/70 mt-1">Ventas + Propinas</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-3 h-3 rounded-full bg-blue-400"></span>
                    <h6 class="text-gray-400 font-bold text-xs uppercase tracking-wider">Pedidos Pagados</h6>
                </div>
                <h3 class="text-2xl font-black text-gray-800">{{ $cantidadPedidos }}</h3>
                <p class="text-xs text-gray-400 mt-1">Comandas cerradas</p>
            </div>
        </div>

        <!-- Desglose por método de pago -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100">
                <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                    <span>💳</span> Desglose por Método de Pago
                </h3>
                <p class="text-gray-500 text-sm mt-1">Cuadre de caja física vs. tarjetas y transferencias</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-6">
                @php
                    $metodosLabels = [
                        'efectivo' => ['label' => 'Efectivo', 'emoji' => '💵', 'color' => 'bg-terracotta/10 text-terracotta border-terracotta/20'],
                        'tarjeta' => ['label' => 'Tarjeta', 'emoji' => '💳', 'color' => 'bg-blue-50 text-blue-500 border-blue-100'],
                        'transferencia' => ['label' => 'Transferencia', 'emoji' => '📱', 'color' => 'bg-purple-50 text-purple-500 border-purple-100'],
                    ];
                @endphp

                @foreach ($metodos as $metodo)
                    @php
                        $d = $desglose[$metodo] ?? ['monto' => 0, 'propinas' => 0, 'cantidad' => 0, 'total_con_propina' => 0];
                        $info = $metodosLabels[$metodo] ?? ['label' => ucfirst($metodo), 'emoji' => '💳', 'color' => 'bg-gray-50 text-gray-500 border-gray-100'];
                    @endphp
                    <div class="p-5 rounded-2xl border {{ $info['color'] }} bg-white/50">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl">{{ $info['emoji'] }}</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ $info['label'] }}</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Monto</span>
                                <span class="font-bold text-gray-800">${{ number_format($d['monto'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Propinas</span>
                                <span class="font-bold text-yellow-600">${{ number_format($d['propinas'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t border-gray-100">
                                <span class="text-gray-500">Pedidos</span>
                                <span class="font-bold text-gray-800">{{ $d['cantidad'] }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                <span class="text-xs font-bold text-gray-400 uppercase">Total con propina</span>
                                <span class="font-black text-lg text-terracotta">${{ number_format($d['total_con_propina'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Detalle de pedidos (contenedor para PDF) -->
        <div id="reporte-pdf" class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                            <span>🧾</span> Detalle de Pedidos Pagados
                        </h3>
                        <p class="text-gray-500 text-sm mt-1">Listado de comandas cerradas en el {{ $periodoLabels[$periodo] }} {{ $etiqueta }}</p>
                    </div>
                    <span class="text-xs font-bold text-terracotta bg-terracotta/10 px-2 py-1 rounded">
                        Generado: {{ now()->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider"># Pedido</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Mesa</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Barista</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Hora</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Método</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Total</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Propina</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Total + Propina</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pedidos as $pedido)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-800">#{{ $pedido->id }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">Mesa {{ $pedido->mesa->numero ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $pedido->garzon->name ?? 'Desconocido' }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-600">{{ \Carbon\Carbon::parse($pedido->created_at)->format('H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-right font-medium">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold 
                                        @if($pedido->metodo_pago === 'efectivo') bg-terracotta/10 text-terracotta
                                        @elseif($pedido->metodo_pago === 'tarjeta') bg-blue-50 text-blue-500
                                        @else bg-purple-50 text-purple-500 @endif">
                                        {{ ucfirst($pedido->metodo_pago) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-gray-800">${{ number_format($pedido->total, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-500">${{ number_format($pedido->propina, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-terracotta">${{ number_format($pedido->total + $pedido->propina, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        <tr class="bg-gray-50 border-t border-gray-200 font-bold">
                            <td colspan="4" class="px-6 py-4 text-sm text-gray-600">TOTAL DEL {{ strtoupper($periodoLabels[$periodo]) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-600"></td>
                            <td class="px-6 py-4 text-sm text-right font-black text-gray-800">${{ number_format($totalVentas, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-right font-black text-yellow-600">${{ number_format($totalPropinas, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-right font-black text-terracotta">${{ number_format($totalGeneral, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function descargarPDF(e) {
            e.preventDefault();

            const elemento = document.getElementById('reporte-pdf');
            if (!elemento) {
                alert('No hay contenido para exportar.');
                return;
            }

            const opts = {
                margin: [10, 10, 10, 10],
                filename: 'reporte-z-{{ $periodo }}-{{ $valor }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, logging: true, letterRendering: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opts).from(elemento).save();
        }

        // Ajustar tipo de input según período seleccionado
        const selectPeriodo = document.getElementById('periodo');
        const inputValor = document.getElementById('valor');

        function actualizarInput() {
            if (selectPeriodo.value === 'dia') {
                inputValor.type = 'date';
                inputValor.value = '{{ $valor }}';
            } else if (selectPeriodo.value === 'mes') {
                inputValor.type = 'month';
                inputValor.value = '{{ $valor }}';
            } else {
                inputValor.type = 'number';
                inputValor.min = 2000;
                inputValor.max = 2100;
                inputValor.value = '{{ $valor }}';
            }
        }

        selectPeriodo.addEventListener('change', actualizarInput);
        actualizarInput();
    </script>
@endsection