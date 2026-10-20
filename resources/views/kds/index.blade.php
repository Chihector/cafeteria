<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina KDS - Café Rosé</title>
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
    </style>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#111318] text-white h-screen overflow-hidden font-sans" x-data="kdsApp()">

<div class="h-full flex flex-col overflow-hidden">
    
    <!-- Kitchen header -->
    <div class="flex items-center justify-between px-6 py-3 border-b border-white/5 bg-[#0E1016] flex-none">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-terracotta rounded-lg flex items-center justify-center shadow-lg shadow-terracotta/20">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <div>
                    <p class="text-white font-black text-sm tracking-tight">KDS Cocina</p>
                    <p class="text-white/30 text-xs">Sistema de comandas en vivo con paginación infinita</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-white/70 text-xs font-bold leading-tight">{{ Auth::user()->name }}</p>
                    <p class="text-white/30 text-[10px] leading-tight">{{ Auth::user()->roleLabel() }}</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-1 rounded-full border"
                      :class="realtimeConectado ? 'text-green-400 border-green-400/30 bg-green-400/10' : 'text-amber-400 border-amber-400/30 bg-amber-400/10'"
                      x-text="realtimeConectado ? '● En vivo' : '○ Auto-refresco'"></span>
                <div class="text-white/40 text-xs font-mono font-medium" x-text="reloj"></div>
                <button @click="cargarTodo()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/5 hover:bg-white/10 text-white/70 transition-all border border-white/10">
                    ↻ Refrescar
                </button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/5 hover:bg-white/10 text-white/70 transition-all border border-white/10">
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Columns -->
    <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-0 overflow-hidden">
        
        <!-- Columna Pendientes -->
        <div class="flex flex-col overflow-hidden border-r border-white/5 last:border-r-0">
            <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between flex-none bg-[#111318]/50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-terracotta shadow-[0_0_8px_rgba(226,88,34,0.6)]"></span>
                    <span class="font-bold text-sm text-terracotta">Nuevos / Pendientes</span>
                </div>
                <span class="text-xs font-black px-2 py-0.5 rounded-full bg-terracotta/20 text-terracotta" x-text="pedidosPendientes.length"></span>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-hide" 
                 x-ref="colPendientes" 
                 @scroll="scrollInfinito('pendiente', $event)">
                <div x-show="pedidosPendientes.length === 0 && !cargandoPendiente" class="flex items-center justify-center h-24 text-white/20 text-sm font-medium">Sin comandas</div>
                <div x-show="cargandoPendiente && pedidosPendientes.length === 0" class="flex items-center justify-center h-24 text-white/40 text-xs font-mono animate-pulse">Cargando...</div>
                <template x-for="pedido in pedidosPendientes" :key="pedido.id">
                    <div class="rounded-2xl border bg-[#1A1D25] border-terracotta/40 p-4 flex flex-col gap-3 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg bg-terracotta text-white shadow-md shadow-terracotta/20" x-text="pedido.mesa ? pedido.mesa.numero : '-'"></div>
                                <div>
                                    <p class="text-white font-bold text-sm" x-text="pedido.mesa ? 'Mesa ' + pedido.mesa.numero : 'Para llevar'"></p>
                                    <p class="text-white/40 text-xs font-mono" x-text="'#' + pedido.id"></p>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-full bg-white/10 text-white/60" x-text="tiempoTranscurrido(pedido.created_at)"></span>
                        </div>
                        <div class="space-y-1.5 mt-2">
                            <template x-for="detalle in pedido.detalles" :key="detalle.id">
                                <div>
                                    <div class="flex items-start gap-2">
                                        <span class="text-terracotta font-black text-sm w-5 text-right flex-none" x-text="detalle.cantidad + '×'"></span>
                                        <span class="text-white font-medium text-sm" x-text="detalle.producto.nombre"></span>
                                    </div>
                                    <template x-if="detalle.notas_especiales">
                                        <div class="ml-7 mt-0.5">
                                            <span class="inline-block text-[10px] font-black px-2 py-0.5 rounded border bg-yellow-400/10 text-yellow-300 border-yellow-400/20 uppercase tracking-wider" x-text="detalle.notas_especiales"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <button @click="cambiarEstado(pedido.id, 'preparando')" class="w-full py-2.5 mt-2 rounded-xl font-bold text-sm transition-all active:scale-95 bg-terracotta hover:bg-terracotta-dark text-white shadow-lg">
                            ▶ Iniciar preparación
                        </button>
                    </div>
                </template>
                <div x-show="hayMasPendiente && !cargandoPendiente" class="flex justify-center py-4">
                    <button @click="cargarMas('pendiente')" class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/70 text-xs font-bold border border-white/10 transition-all">
                        Cargar más comandas
                    </button>
                </div>
                <div x-show="cargandoPendiente" class="flex justify-center py-4 text-white/40 text-xs font-mono animate-pulse">Cargando más...</div>
            </div>
        </div>

        <!-- Columna En Preparación -->
        <div class="flex flex-col overflow-hidden border-r border-white/5 last:border-r-0">
            <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between flex-none bg-[#111318]/50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-yellow-400 shadow-[0_0_8px_rgba(250,204,21,0.6)]"></span>
                    <span class="font-bold text-sm text-yellow-400">En Preparación</span>
                </div>
                <span class="text-xs font-black px-2 py-0.5 rounded-full bg-yellow-400/20 text-yellow-400" x-text="pedidosPreparando.length"></span>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-hide" 
                 x-ref="colPreparando" 
                 @scroll="scrollInfinito('preparando', $event)">
                <div x-show="pedidosPreparando.length === 0 && !cargandoPreparando" class="flex items-center justify-center h-24 text-white/20 text-sm font-medium">Sin comandas</div>
                <div x-show="cargandoPreparando && pedidosPreparando.length === 0" class="flex items-center justify-center h-24 text-white/40 text-xs font-mono animate-pulse">Cargando...</div>
                <template x-for="pedido in pedidosPreparando" :key="pedido.id">
                    <div class="rounded-2xl border bg-[#1A1D25] border-yellow-500/30 p-4 flex flex-col gap-3 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg bg-yellow-400 text-black shadow-md shadow-yellow-400/20" x-text="pedido.mesa ? pedido.mesa.numero : '-'"></div>
                                <div>
                                    <p class="text-white font-bold text-sm" x-text="pedido.mesa ? 'Mesa ' + pedido.mesa.numero : 'Para llevar'"></p>
                                    <p class="text-white/40 text-xs font-mono" x-text="'#' + pedido.id"></p>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-full bg-yellow-400/20 text-yellow-400" x-text="tiempoTranscurrido(pedido.created_at)"></span>
                        </div>
                        <div class="space-y-1.5 mt-2">
                            <template x-for="detalle in pedido.detalles" :key="detalle.id">
                                <div>
                                    <div class="flex items-start gap-2">
                                        <span class="text-terracotta font-black text-sm w-5 text-right flex-none" x-text="detalle.cantidad + '×'"></span>
                                        <span class="text-white font-medium text-sm" x-text="detalle.producto.nombre"></span>
                                    </div>
                                    <template x-if="detalle.notas_especiales">
                                        <div class="ml-7 mt-0.5">
                                            <span class="inline-block text-[10px] font-black px-2 py-0.5 rounded border bg-yellow-400/10 text-yellow-300 border-yellow-400/20 uppercase tracking-wider" x-text="detalle.notas_especiales"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <button @click="cambiarEstado(pedido.id, 'listo')" class="w-full py-2.5 mt-2 rounded-xl font-bold text-sm transition-all active:scale-95 bg-green-500 hover:bg-green-400 text-white shadow-lg">
                            ✓ Marcar como listo
                        </button>
                    </div>
                </template>
                <div x-show="hayMasPreparando && !cargandoPreparando" class="flex justify-center py-4">
                    <button @click="cargarMas('preparando')" class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/70 text-xs font-bold border border-white/10 transition-all">
                        Cargar más comandas
                    </button>
                </div>
                <div x-show="cargandoPreparando" class="flex justify-center py-4 text-white/40 text-xs font-mono animate-pulse">Cargando más...</div>
            </div>
        </div>

        <!-- Columna Listos -->
        <div class="flex flex-col overflow-hidden border-r border-white/5 last:border-r-0">
            <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between flex-none bg-[#111318]/50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-400 shadow-[0_0_8px_rgba(74,222,128,0.6)]"></span>
                    <span class="font-bold text-sm text-green-400">Listos para Servir</span>
                </div>
                <span class="text-xs font-black px-2 py-0.5 rounded-full bg-green-400/20 text-green-400" x-text="pedidosListos.length"></span>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-hide" 
                 x-ref="colListos" 
                 @scroll="scrollInfinito('listo', $event)">
                <div x-show="pedidosListos.length === 0 && !cargandoListo" class="flex items-center justify-center h-24 text-white/20 text-sm font-medium">Sin comandas</div>
                <div x-show="cargandoListo && pedidosListos.length === 0" class="flex items-center justify-center h-24 text-white/40 text-xs font-mono animate-pulse">Cargando...</div>
                <template x-for="pedido in pedidosListos" :key="pedido.id">
                    <div class="rounded-2xl border bg-[#1A1D25] border-green-500/30 p-4 flex flex-col gap-3 shadow-lg opacity-80">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg bg-green-400 text-black shadow-md shadow-green-400/20" x-text="pedido.mesa ? pedido.mesa.numero : '-'"></div>
                                <div>
                                    <p class="text-white font-bold text-sm" x-text="pedido.mesa ? 'Mesa ' + pedido.mesa.numero : 'Para llevar'"></p>
                                    <p class="text-white/40 text-xs font-mono" x-text="'#' + pedido.id"></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-center gap-2 py-2.5 mt-2 rounded-xl bg-green-500/10 border border-green-500/20">
                            <span class="text-green-400 text-sm font-bold tracking-wide">✓ LISTO PARA SERVIR</span>
                        </div>
                    </div>
                </template>
                <div x-show="hayMasListo && !cargandoListo" class="flex justify-center py-4">
                    <button @click="cargarMas('listo')" class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/70 text-xs font-bold border border-white/10 transition-all">
                        Cargar más comandas
                    </button>
                </div>
                <div x-show="cargandoListo" class="flex justify-center py-4 text-white/40 text-xs font-mono animate-pulse">Cargando más...</div>
            </div>
        </div>

    </div>
</div>

<script>
    function kdsApp() {
        return {
            pedidos: [],
            pedidosPendientes: [],
            pedidosPreparando: [],
            pedidosListos: [],
            paginaPendiente: 1,
            paginaPreparando: 1,
            paginaListo: 1,
            hayMasPendiente: true,
            hayMasPreparando: true,
            hayMasListo: true,
            cargandoPendiente: false,
            cargandoPreparando: false,
            cargandoListo: false,
            now: new Date(),
            reloj: '',
            realtimeConectado: false,

            init() {
                this.cargarTodo();
                this.iniciarTiempoReal();

                // Respaldo: si el WebSocket no está conectado, refresca cada 12 s.
                setInterval(() => this.refrescarRespaldo(), 12000);

                window.addEventListener('cafeteria:realtime', (evento) => {
                    this.realtimeConectado = evento.detail.conectado;
                });

                // Reloj cada segundo
                setInterval(() => {
                    this.now = new Date();
                    this.reloj = this.now.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }, 1000);
            },

            iniciarTiempoReal() {
                if (!window.CafeRoseRealtime) return;

                window.CafeRoseRealtime.listo(() => {
                    window.CafeRoseRealtime.suscribir('cocina', 'pedido.creado', () => {
                        window.CafeRoseRealtime.beep();
                        this.cargarTodo();
                    });

                    window.CafeRoseRealtime.suscribir('cocina', 'pedido.actualizado', () => {
                        this.cargarTodo();
                    });
                });
            },

            refrescarRespaldo() {
                if (document.hidden || this.realtimeConectado) return;
                this.cargarTodo();
            },

            async cargarTodo() {
                this.paginaPendiente = 1;
                this.paginaPreparando = 1;
                this.paginaListo = 1;
                this.hayMasPendiente = true;
                this.hayMasPreparando = true;
                this.hayMasListo = true;
                await Promise.all([
                    this.cargarEstado('pendiente'),
                    this.cargarEstado('preparando'),
                    this.cargarEstado('listo'),
                ]);
            },

            async cargarEstado(estado) {
                const keyCargando = 'cargando' + estado.charAt(0).toUpperCase() + estado.slice(1);
                const keyPagina = 'pagina' + estado.charAt(0).toUpperCase() + estado.slice(1);
                const keyHayMas = 'hayMas' + estado.charAt(0).toUpperCase() + estado.slice(1);

                // Los arrays usan plural: pendiente -> pedidosPendientes, etc.
                const listas = {
                    pendiente: 'pedidosPendientes',
                    preparando: 'pedidosPreparando',
                    listo: 'pedidosListos',
                };
                const keyLista = listas[estado];

                if (!keyLista || !this[keyHayMas]) return;
                this[keyCargando] = true;

                try {
                    const res = await fetch(`/api/kds/pedidos?estado=${estado}&page=${this[keyPagina]}&per_page=15`);
                    const data = await res.json();

                    const nuevos = data.data || [];
                    this[keyLista] = this[keyPagina] === 1 ? nuevos : [...this[keyLista], ...nuevos];

                    this[keyHayMas] = !(data.current_page >= data.last_page);
                    this[keyPagina]++;

                    // Sincronizar lista maestra (para compatibilidad)
                    this.pedidos = [...this.pedidosPendientes, ...this.pedidosPreparando, ...this.pedidosListos];
                } catch (e) {
                    console.error('Error cargando ' + estado, e);
                } finally {
                    this[keyCargando] = false;
                }
            },

            async cargarMas(estado) {
                await this.cargarEstado(estado);
            },

            async scrollInfinito(estado, event) {
                const elemento = event.target;
                // Umbral: cuando se haya scrolleado el 85% del contenedor
                const umbral = elemento.scrollHeight - elemento.scrollTop - elemento.clientHeight;
                if (umbral < 120) {
                    await this.cargarEstado(estado);
                }
            },

            async cambiarEstado(id, nuevoEstado) {
                try {
                    const res = await fetch(`/api/kds/pedidos/${id}/estado`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ estado: nuevoEstado })
                    });
                    if (res.ok) {
                        await this.cargarTodo();
                    }
                } catch (e) {
                    console.error(e);
                }
            },

            tiempoTranscurrido(fechaStr) {
                const creado = new Date(fechaStr);
                const diffMs = this.now - creado;
                const min = Math.floor(diffMs / 60000);
                const seg = Math.floor((diffMs % 60000) / 1000);
                return min.toString().padStart(2, '0') + ':' + seg.toString().padStart(2, '0');
            },

            // Getters de compatibilidad (mantenidos por si se usan en templates)
            get pedidosPendientesGetter() { return this.pedidosPendientes; },
            get pedidosPreparandoGetter() { return this.pedidosPreparando; },
            get pedidosListosGetter() { return this.pedidosListos; },
        }
    }
</script>
@include('layouts.realtime')
</body>
</html>