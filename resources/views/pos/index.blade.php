<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Café Rosé</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        terracotta: { DEFAULT: '#E83E8C', dark: '#C2185B', light: '#F48FB1' }
                    }
                }
            }
        }
    </script>
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .pulse-badge { animation: pulse-ring 2s infinite; }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(232, 62, 140, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(232, 62, 140, 0); }
            100% { box-shadow: 0 0 0 0 rgba(232, 62, 140, 0); }
        }
    </style>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 h-[100dvh] overflow-hidden font-sans text-gray-800" x-data="posApp()">

<div class="flex h-full flex-col lg:flex-row">
    
    <!-- LEFT PANEL: Tables & Menu -->
    <div class="flex-1 flex flex-col bg-gray-50 lg:border-r border-gray-200 min-h-0">
        
        <!-- Header -->
        <div class="bg-white border-b border-gray-100 px-6 pt-6 pb-4 shadow-sm z-10 flex-none">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Turno activo</p>
                    <p class="text-sm font-semibold text-gray-700">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-gray-400">{{ Auth::user()->roleLabel() }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] font-bold px-2 py-1 rounded-full border"
                          :class="realtimeConectado ? 'text-green-600 border-green-200 bg-green-50' : 'text-amber-600 border-amber-200 bg-amber-50'"
                          x-text="realtimeConectado ? '● Tiempo real' : '○ Auto-refresco'"></span>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-400 inline-block pulse-badge"></span>
                        <span class="text-xs font-medium text-green-600">En línea</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-gray-400 hover:text-terracotta transition-colors">Salir</button>
                    </form>
                </div>
            </div>
            
            <!-- Table grid selector -->
            <div class="mt-4">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-2">Selecciona una mesa</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto scrollbar-hide pb-2">
                    <template x-for="mesa in mesas" :key="mesa.id">
                        <button @click="mesaSeleccionada = mesa" 
                            class="relative flex flex-col p-3 rounded-xl border transition-all text-left overflow-hidden min-h-[5rem]"
                            :class="{
                                'bg-terracotta border-terracotta shadow-md shadow-terracotta/20': mesa.estado === 'ocupada',
                                'bg-white border-gray-200 hover:border-terracotta/50': mesa.estado !== 'ocupada',
                                'ring-2 ring-terracotta ring-offset-2': mesaSeleccionada && mesaSeleccionada.id === mesa.id
                            }">
                            
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-black text-lg leading-none" :class="mesa.estado === 'ocupada' ? 'text-white' : 'text-gray-800'" x-text="'Mesa ' + mesa.numero"></span>
                                <span x-show="mesa.estado === 'ocupada'" class="text-[9px] font-bold bg-white/20 text-white px-1.5 py-0.5 rounded backdrop-blur-sm">OCUPADA</span>
                            </div>
                            
                            <template x-if="mesa.estado === 'ocupada'">
                                <div class="mt-auto pt-1">
                                    <p class="text-xs text-white/90 font-bold truncate flex items-center gap-1"><span class="text-[10px]">☕</span> <span x-text="mesa.barista"></span></p>
                                    <p class="text-[10px] text-white/70 truncate flex items-center gap-1"><span class="text-[10px]">⏱️</span> <span x-text="mesa.tiempo_ocupada"></span></p>
                                </div>
                            </template>
                            <template x-if="mesa.estado !== 'ocupada'">
                                <div class="mt-auto pt-1 flex flex-col justify-end">
                                    <p class="text-xs text-gray-400 font-bold">Disponible</p>
                                </div>
                            </template>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Categories & Search -->
        <div class="bg-white border-b border-gray-100 px-6 py-3 flex-none flex flex-col gap-3">
            <div class="relative">
                <input type="text" x-model="searchQuery" placeholder="Buscar producto rápido..." class="w-full bg-gray-100 border-none rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terracotta/30">
                <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
            </div>
            <div class="flex gap-3 overflow-x-auto scrollbar-hide">
                <template x-for="cat in categorias" :key="cat.id">
                    <button @click="categoriaSeleccionada = cat.id; searchQuery = ''"
                        class="flex-none flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-all"
                        :class="categoriaSeleccionada === cat.id && !searchQuery ? 'bg-terracotta text-white shadow-sm' : 'bg-gray-100 text-gray-500 lg:hover:bg-gray-200'">
                        <span x-text="cat.icon"></span>
                        <span x-text="cat.label"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Products -->
        <div class="flex-1 overflow-y-auto px-6 py-5 scrollbar-hide pb-28 lg:pb-5">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <template x-for="producto in productosFiltrados" :key="producto.id">
                    <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 flex flex-col cursor-pointer transition-transform lg:hover:-translate-y-1 lg:hover:shadow-md" @click="abrirCustomizacion(producto)">
                        <div class="relative">
                            <img :src="producto.imagen || 'https://via.placeholder.com/400'" alt="" class="w-full h-32 object-cover bg-gray-200" />
                            <span x-show="producto.destacado" class="absolute top-2 left-2 bg-terracotta text-white text-[10px] font-bold px-2 py-0.5 rounded-full">⭐ Popular</span>
                            <span x-show="producto.vegetariano" class="absolute top-2 right-2 bg-green-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">V</span>
                        </div>
                        <div class="p-3 flex flex-col flex-1">
                            <p class="font-bold text-gray-800 text-sm leading-tight line-clamp-2" x-text="producto.nombre"></p>
                            <p x-show="producto.controlar_stock" class="text-[10px] text-gray-400 font-semibold mt-1" x-text="'Stock: ' + producto.stock"></p>
                            <div class="flex items-center justify-between mt-auto pt-2">
                                <span class="font-bold text-terracotta text-sm" x-text="formatoPrecio(producto.precio)"></span>
                                <button class="w-8 h-8 rounded-lg bg-terracotta/10 text-terracotta flex items-center justify-center lg:group-hover:bg-terracotta lg:group-hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Mobile Cart Floating Button -->
    <button @click="cartOpenMobile = true" class="lg:hidden fixed bottom-4 left-4 right-4 bg-gray-900 text-white rounded-2xl p-4 shadow-2xl z-30 flex items-center justify-between" x-show="!cartOpenMobile">
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-full bg-terracotta flex items-center justify-center font-bold text-sm" x-text="carrito.length"></span>
            <span class="font-bold">Ver Pedido</span>
        </div>
        <span class="font-black text-lg" x-text="formatoPrecio(totalCarrito)"></span>
    </button>

    <!-- RIGHT PANEL: Cart -->
    <div class="w-full lg:w-96 flex flex-col bg-white shadow-[-4px_0_15px_rgba(0,0,0,0.03)] z-40 fixed inset-x-0 bottom-0 rounded-t-3xl lg:rounded-none lg:static lg:h-full transition-transform duration-300"
         :class="cartOpenMobile ? 'h-[85dvh] translate-y-0' : 'translate-y-full lg:translate-y-0 lg:h-full'">
        <div class="px-6 pt-6 pb-4 border-b border-gray-100 flex justify-between items-start">
            <div>
                <h2 class="text-lg font-black text-gray-800 flex items-center gap-2">
                    <span>📝</span> Pedido Actual
                </h2>
                <p class="text-xs text-gray-400 mt-1" x-text="mesaSeleccionada ? 'Mesa ' + mesaSeleccionada.numero : 'Selecciona una mesa para empezar'"></p>
            </div>
            <button @click="cartOpenMobile = false" class="lg:hidden text-gray-400 hover:bg-gray-100 p-2 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 scrollbar-hide space-y-3">
            <div x-show="carrito.length === 0" class="h-full flex flex-col items-center justify-center text-gray-400">
                <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <p class="text-sm font-medium">Carrito vacío</p>
            </div>
            
            <template x-for="(item, index) in carrito" :key="index">
                <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 flex gap-3 relative group">
                    <img :src="item.producto.imagen || 'https://via.placeholder.com/150'" class="w-14 h-14 rounded-lg object-cover bg-gray-200 flex-none" />
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <p class="font-bold text-sm text-gray-800 leading-tight" x-text="item.producto.nombre"></p>
                            <button @click="removerItem(index)" class="text-gray-400 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <p class="text-xs text-terracotta font-bold mt-1" x-text="formatoPrecio(item.producto.precio)"></p>
                        <p x-show="item.nota" class="text-[10px] bg-white border border-gray-200 text-gray-500 px-2 py-1 rounded mt-2 font-medium" x-text="item.nota"></p>
                    </div>
                </div>
            </template>
        </div>

        <div class="p-6 bg-white border-t border-gray-100 shadow-[0_-4px_15px_rgba(0,0,0,0.02)] flex-none">
            <template x-if="carrito.length > 0 || !mesaSeleccionada || mesaSeleccionada.estado !== 'ocupada'">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-500 font-medium">Total</span>
                        <span class="font-black text-2xl text-gray-800" x-text="formatoPrecio(totalCarrito)"></span>
                    </div>
                    <button @click="enviarPedido" :disabled="carrito.length === 0 || loading" 
                        class="w-full bg-terracotta text-white font-bold py-4 rounded-2xl hover:bg-terracotta-dark transition-all shadow-lg active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2 text-lg">
                        <span x-show="!loading" class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                            Enviar a Cocina
                        </span>
                        <span x-show="loading">Enviando...</span>
                    </button>
                </div>
            </template>

            <template x-if="carrito.length === 0 && mesaSeleccionada && mesaSeleccionada.estado === 'ocupada'">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-500 font-medium">Cuenta Mesa</span>
                        <span class="font-black text-2xl text-terracotta" x-text="formatoPrecio(mesaSeleccionada.total || 0)"></span>
                    </div>
                    <button @click="abrirCobro()"
                        class="w-full bg-green-500 text-white font-bold py-4 rounded-2xl hover:bg-green-600 transition-all shadow-lg shadow-green-500/30 active:scale-95 flex items-center justify-center gap-2 text-lg">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        Cobrar Mesa
                    </button>
                    <button @click="abrirAnularModal()"
                        class="w-full mt-3 bg-white text-red-600 border border-red-200 font-bold py-3 rounded-2xl hover:bg-red-50 transition-all flex items-center justify-center gap-2 text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Anular Pedido
                    </button>
                </div>
            </template>
            <!-- Modal Customización -->
    <div x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modalAbierto = false"></div>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden flex flex-col max-h-[90dvh]">
            <div class="p-6 overflow-y-auto">
                <div class="flex gap-4 mb-5">
                    <img :src="productoActual?.imagen" class="w-20 h-20 rounded-xl object-cover bg-gray-200 flex-none" />
                    <div>
                        <p class="font-black text-lg text-gray-800 leading-tight" x-text="productoActual?.nombre"></p>
                        <p class="text-terracotta font-bold text-lg mt-1" x-text="productoActual ? formatoPrecio(productoActual.precio) : ''"></p>
                    </div>
                </div>
                
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Observaciones rápidas</p>
                <div class="flex flex-wrap gap-2 mb-5">
                    <template x-for="n in notasRapidas">
                        <button @click="toggleNota(n)" 
                            class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                            :class="notaActual.includes(n) ? 'bg-terracotta text-white border-terracotta' : 'bg-gray-50 text-gray-600 border-gray-200'">
                            <span x-text="n"></span>
                        </button>
                    </template>
                </div>
                
                <textarea x-model="notaActual" rows="2" placeholder="Otras indicaciones (ej. Alergias, términos de cocción...)" 
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-terracotta/30 focus:border-terracotta mb-2 resize-none"></textarea>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 flex gap-3">
                <button @click="modalAbierto = false" class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50">Cancelar</button>
                <button @click="agregarAlCarrito" class="flex-1 px-4 py-3 bg-terracotta text-white font-bold rounded-xl hover:bg-terracotta-dark shadow-md shadow-terracotta/20">Agregar</button>
            </div>
        </div>
    </div>

    <!-- Modal Cobro -->
    <div x-show="modalCobroAbierto" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modalCobroAbierto = false"></div>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden flex flex-col">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-black text-lg text-gray-800">Cobrar Mesa <span x-text="mesaSeleccionada?.numero"></span></h3>
                <button @click="modalCobroAbierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6">
                <!-- Método de Pago -->
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Método de Pago</p>
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <button @click="metodoPago = 'efectivo'" class="py-3 rounded-xl border flex flex-col items-center justify-center gap-1 transition-all" :class="metodoPago === 'efectivo' ? 'bg-terracotta/10 border-terracotta text-terracotta' : 'bg-white border-gray-200 text-gray-500'">
                        <span class="text-xl">💵</span>
                        <span class="text-xs font-bold">Efectivo</span>
                    </button>
                    <button @click="metodoPago = 'tarjeta'" class="py-3 rounded-xl border flex flex-col items-center justify-center gap-1 transition-all" :class="metodoPago === 'tarjeta' ? 'bg-terracotta/10 border-terracotta text-terracotta' : 'bg-white border-gray-200 text-gray-500'">
                        <span class="text-xl">💳</span>
                        <span class="text-xs font-bold">Tarjeta</span>
                    </button>
                    <button @click="metodoPago = 'transferencia'" class="py-3 rounded-xl border flex flex-col items-center justify-center gap-1 transition-all" :class="metodoPago === 'transferencia' ? 'bg-terracotta/10 border-terracotta text-terracotta' : 'bg-white border-gray-200 text-gray-500'">
                        <span class="text-xl">📱</span>
                        <span class="text-xs font-bold">Transf.</span>
                    </button>
                </div>

                <!-- Propina -->
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100 mb-6 cursor-pointer">
                    <div>
                        <span class="block text-sm font-bold text-gray-800">Agregar Propina Sugerida (10%)</span>
                        <span class="block text-xs text-gray-500 font-medium mt-1" x-text="formatoPrecio(montoPropina)"></span>
                    </div>
                    <div class="relative">
                        <input type="checkbox" x-model="propinaSugerida" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </div>
                </label>

                <!-- Totales -->
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Subtotal</span>
                        <span x-text="formatoPrecio(mesaSeleccionada?.total || 0)"></span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500" x-show="propinaSugerida">
                        <span>Propina (10%)</span>
                        <span x-text="formatoPrecio(montoPropina)"></span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                        <span class="text-gray-800 font-bold">Total a Pagar</span>
                        <span class="font-black text-2xl text-terracotta" x-text="formatoPrecio(totalConPropina)"></span>
                    </div>
                </div>

                <!-- Botón Pagar -->
                <button @click="procesarCobro" :disabled="loading" class="w-full bg-green-500 text-white font-bold py-4 rounded-xl hover:bg-green-600 transition-all shadow-lg shadow-green-500/30 flex items-center justify-center gap-2 text-lg active:scale-95 disabled:opacity-50">
                    <span x-show="!loading" class="flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        Confirmar y Cobrar
                    </span>
                    <span x-show="loading">Procesando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Anular Pedido -->
    <div x-show="modalAnularAbierto" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modalAnularAbierto = false"></div>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden flex flex-col">
            <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex justify-between items-center">
                <h3 class="font-black text-lg text-red-700">Anular Pedido <span x-text="mesaSeleccionada?.numero"></span></h3>
                <button @click="modalAnularAbierto = false" class="text-red-400 hover:text-red-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="p-6">
                <div class="flex items-start gap-3 p-3 bg-red-50 border border-red-100 rounded-xl mb-4">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <p class="text-xs text-red-800 leading-snug">Se repondrá el stock de los productos, la mesa quedará libre y la acción quedará registrada en auditoría. <strong>No se puede anular un pedido ya pagado.</strong></p>
                </div>
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 block">Motivo (obligatorio)</label>
                <textarea x-model="motivoAnulacion" rows="2" placeholder="Ej: Cliente se retiró sin ordenar" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-red-400 mb-4 resize-none"></textarea>
                <button @click="confirmarAnulacion()" :disabled="loading || motivoAnulacion.trim().length < 5" class="w-full bg-red-600 text-white font-bold py-4 rounded-xl hover:bg-red-700 transition-all flex items-center justify-center gap-2 text-lg active:scale-95 disabled:opacity-50">
                    <span x-show="!loading">Confirmar Anulación</span>
                    <span x-show="loading">Anulando...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function posApp() {
        return {
            mesas: [],
            productos: [],
            mesaSeleccionada: null,
            categoriaSeleccionada: 'coffee',
            searchQuery: '',
            carrito: [],
            loading: false,
            cartOpenMobile: false,
            realtimeConectado: false,
            
            // Modal state
            modalAbierto: false,
            productoActual: null,
            notaActual: '',
            notasRapidas: ['Sin mayo', 'Sin cebolla', 'Extra queso', 'Término medio', 'Bien cocido', 'Sin hielo'],
            
            modalCobroAbierto: false,
            metodoPago: 'efectivo',
            propinaSugerida: true, // 10%
            modalAnularAbierto: false,
            motivoAnulacion: '',
            
            categorias: [
                { id: 'coffee', label: 'Cafés', icon: '☕' },
                { id: 'pastries', label: 'Pastelería', icon: '🥐' },
                { id: 'sandwiches', label: 'Sándwiches', icon: '🥪' },
                { id: 'cold_drinks', label: 'Bebidas Frías', icon: '🥤' },
            ],

            init() {
                this.fetchData();
                this.iniciarTiempoReal();

                // Respaldo: si el WebSocket no está conectado, refresca cada 12 s.
                setInterval(() => this.refrescarRespaldo(), 12000);

                window.addEventListener('cafeteria:realtime', (evento) => {
                    this.realtimeConectado = evento.detail.conectado;
                });
            },

            iniciarTiempoReal() {
                if (!window.CafeRoseRealtime) return;

                window.CafeRoseRealtime.listo(() => {
                    window.CafeRoseRealtime.suscribir('pos', 'pedido.creado', () => {
                        this.fetchData();
                    });

                    window.CafeRoseRealtime.suscribir('pos', 'pedido.actualizado', (payload) => {
                        this.fetchData();

                        // Mesa liberada desde otro POS: aviso sonoro.
                        if (payload && payload.mesa_estado === 'disponible') {
                            window.CafeRoseRealtime.beep();
                        }
                    });
                });
            },

            refrescarRespaldo() {
                if (document.hidden || this.realtimeConectado) return;
                this.fetchData();
            },

            async fetchData() {
                try {
                    const resMesas = await fetch('/api/mesas');
                    this.mesas = await resMesas.json();
                    
                    const resProd = await fetch('/api/productos');
                    this.productos = await resProd.json();
                } catch (e) {
                    console.error("Error cargando datos", e);
                }
            },

            get productosFiltrados() {
                if (this.searchQuery.trim() !== '') {
                    const term = this.searchQuery.toLowerCase();
                    return this.productos.filter(p => p.nombre.toLowerCase().includes(term) || (p.descripcion && p.descripcion.toLowerCase().includes(term)));
                }
                return this.productos.filter(p => p.categoria === this.categoriaSeleccionada);
            },

            get totalCarrito() {
                return this.carrito.reduce((sum, item) => sum + parseFloat(item.producto.precio), 0);
            },
            
            get montoPropina() {
                if (!this.mesaSeleccionada || !this.propinaSugerida) return 0;
                return (this.mesaSeleccionada.total || 0) * 0.10;
            },
            
            get totalConPropina() {
                if (!this.mesaSeleccionada) return 0;
                return parseFloat(this.mesaSeleccionada.total || 0) + this.montoPropina;
            },

            formatoPrecio(valor) {
                return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(valor);
            },

            abrirCustomizacion(producto) {
                this.productoActual = producto;
                this.notaActual = '';
                this.modalAbierto = true;
            },

            toggleNota(nota) {
                if (this.notaActual.includes(nota)) {
                    this.notaActual = this.notaActual.replace(nota, '').replace(/,\s*$/, '').trim();
                } else {
                    this.notaActual = this.notaActual ? this.notaActual + ', ' + nota : nota;
                }
            },

            agregarAlCarrito() {
                this.carrito.push({
                    producto: this.productoActual,
                    nota: this.notaActual
                });
                this.modalAbierto = false;
            },

            removerItem(index) {
                this.carrito.splice(index, 1);
            },
            
            abrirCobro() {
                this.metodoPago = 'efectivo';
                this.propinaSugerida = true;
                this.modalCobroAbierto = true;
            },

            abrirAnularModal() {
                this.motivoAnulacion = '';
                this.modalAnularAbierto = true;
            },

            async confirmarAnulacion() {
                if (!this.mesaSeleccionada || !this.mesaSeleccionada.pedido_id) return;

                const motivo = this.motivoAnulacion.trim();
                if (motivo.length < 5) {
                    alert('Indica un motivo de al menos 5 caracteres.');
                    return;
                }
                if (!confirm('¿Anular este pedido? Se repondrá el stock y la mesa quedará libre.')) {
                    return;
                }

                this.loading = true;

                try {
                    const res = await fetch(`/api/pedidos/${this.mesaSeleccionada.pedido_id}/anular`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ motivo })
                    });

                    const data = await res.json();

                    if (res.ok) {
                        alert('✅ Pedido anulado. Stock repuesto y mesa liberada.');
                        this.modalAnularAbierto = false;
                        this.mesaSeleccionada = null;
                        this.fetchData();
                    } else {
                        console.error(data);
                        const detalle = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || '');
                        alert('❌ No se pudo anular el pedido. ' + detalle);
                    }
                } catch (e) {
                    console.error(e);
                    alert('❌ Error de conexión con el servidor.');
                } finally {
                    this.loading = false;
                }
            },

            async enviarPedido() {
                if (this.carrito.length === 0) return;
                
                if (!this.mesaSeleccionada) {
                    alert('⚠️ Por favor selecciona una mesa primero (arriba a la izquierda).');
                    return;
                }
                
                this.loading = true;
                
                const productosPayload = this.carrito.map(item => ({
                    producto_id: item.producto.id,
                    cantidad: 1, // Por ahora 1 por item separado
                    precio_unitario: item.producto.precio,
                    notas_especiales: item.nota
                }));

                try {
                    const res = await fetch('/api/pedidos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            mesa_id: this.mesaSeleccionada.id,
                            observaciones: '',
                            productos: productosPayload
                        })
                    });

                    const data = await res.json();

                    if (res.ok) {
                        this.carrito = [];
                        this.mesaSeleccionada = null;
                        this.cartOpenMobile = false;
                        this.fetchData(); // Recargar estados de mesas

                        if (data.impresion && data.impresion.ok) {
                            // La comanda ya salió por la térmica de red.
                            alert('✅ ¡Pedido enviado y comanda impresa en cocina!');
                        } else {
                            alert('✅ ¡Pedido enviado a cocina correctamente!');
                            // Respaldo: si no hay impresora física, abrir la comanda en el navegador.
                            if (data.pedido && data.pedido.id) {
                                window.open(`/api/pedidos/${data.pedido.id}/comanda`, '_blank', 'width=350,height=600');
                            }
                        }
                    } else {
                        console.error(data);
                        const detalle = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                        alert('❌ Error al enviar el pedido. ' + detalle);
                    }
                } catch (e) {
                    console.error(e);
                    alert('❌ Error de conexión con el servidor.');
                } finally {
                    this.loading = false;
                }
            },
            
            async procesarCobro() {
                if (!this.mesaSeleccionada || !this.mesaSeleccionada.pedido_id) return;
                
                this.loading = true;
                
                try {
                    const res = await fetch(`/api/pedidos/${this.mesaSeleccionada.pedido_id}/pagar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            metodo_pago: this.metodoPago,
                            propina: this.montoPropina
                        })
                    });

                    const data = await res.json();

                    if (res.ok) {
                        // Si la térmica imprimió el ticket, no abrir el respaldo del navegador.
                        if (! (data.impresion && data.impresion.ok)) {
                            window.open(`/api/pedidos/${this.mesaSeleccionada.pedido_id}/ticket`, '_blank', 'width=400,height=600');
                        }

                        alert('✅ ¡Mesa cobrada exitosamente!');
                        this.modalCobroAbierto = false;
                        this.mesaSeleccionada = null;
                        this.fetchData(); // Recargar estados de mesas
                    } else {
                        console.error(data);
                        const detalle = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                        alert('❌ Error al procesar el cobro. ' + detalle);
                    }
                } catch (e) {
                    console.error(e);
                    alert('❌ Error de conexión con el servidor.');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@include('layouts.realtime')
</body>
</html>
