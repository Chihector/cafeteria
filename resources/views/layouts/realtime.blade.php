@php
    $reverb = config('broadcasting.default') === 'reverb'
        ? config('broadcasting.connections.reverb')
        : null;

    $realtimeConfig = $reverb ? [
        'key' => $reverb['key'],
        // En local/LAN usamos el mismo host desde el que se abrió la página
        // (sirve para 127.0.0.1 y para la IP del celular). En producción se
        // define REVERB_HOST con el dominio real del WebSocket.
        'host' => in_array($reverb['options']['host'] ?? null, [null, 'localhost', '127.0.0.1'], true)
            ? request()->getHost()
            : $reverb['options']['host'],
        'port' => (int) ($reverb['options']['port'] ?? 8080),
        'scheme' => $reverb['options']['scheme'] ?? 'http',
        'csrf' => csrf_token(),
    ] : null;
@endphp

@if ($reverb && ! empty($reverb['key']))
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.1/dist/echo.iife.js"></script>
<script>
    (() => {
        const cfg = @json($realtimeConfig);

        let echo = null;
        let conectado = false;
        const pendientes = [];

        function notificar() {
            window.dispatchEvent(new CustomEvent('cafeteria:realtime', { detail: { conectado } }));
        }

        window.CafeRoseRealtime = {
            conectado: () => conectado,

            // Estado del socket: connecting | connected | disconnected | unavailable | failed
            estado: () => {
                try {
                    return echo?.connector?.pusher?.connection?.state ?? 'sin instancia';
                } catch (e) {
                    return 'error: ' + e.message;
                }
            },

            // Ejecuta el callback cuando Echo ya está disponible (o de inmediato si lo está).
            listo: (callback) => {
                if (echo) {
                    callback();
                } else {
                    pendientes.push(callback);
                }
            },

            // Suscribe a un canal privado ('pos' | 'cocina') y a un evento con broadcastAs.
            suscribir: (canal, evento, handler) => {
                window.CafeRoseRealtime.listo(() => {
                    echo.private(canal).listen('.' + evento, (payload) => {
                        try {
                            handler(payload);
                        } catch (e) {
                            console.error('[realtime] Error en handler', evento, e);
                        }
                    });
                });
            },

            // Aviso sonoro corto generado en el navegador (sin archivos externos).
            beep: () => {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) return;
                    const ctx = new Ctx();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = 880;
                    gain.gain.setValueAtTime(0.0001, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.01);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.4);
                    osc.onended = () => ctx.close();
                } catch (e) {
                    // El navegador puede bloquear el audio hasta la primera interacción.
                }
            },
        };

        function iniciar() {
            const EchoConstructor = window.Echo?.default ?? window.Echo;

            if (typeof EchoConstructor !== 'function' || !window.Pusher) {
                console.warn('[realtime] Echo/Pusher no disponibles; se usará auto-refresco.');
                return;
            }

            try {
                echo = new EchoConstructor({
                    broadcaster: 'reverb',
                    key: cfg.key,
                    wsHost: cfg.host,
                    wsPort: cfg.port,
                    wssPort: cfg.port,
                    forceTLS: cfg.scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': cfg.csrf,
                        },
                    },
                    disableStats: true,
                });

                const conexion = echo.connector.pusher.connection;
                const alConectar = () => {
                    conectado = true;
                    notificar();
                };
                const alDesconectar = () => {
                    conectado = false;
                    notificar();
                };

                conexion.bind('connected', alConectar);
                conexion.bind('disconnected', alDesconectar);
                conexion.bind('unavailable', alDesconectar);
                conexion.bind('failed', alDesconectar);
                conexion.bind('state_change', (estados) => {
                    console.debug('[realtime] estado de conexión:', estados.current);
                });
                conexion.bind('error', (error) => {
                    window.__cafeRoseUltimoError = error?.error ?? error?.message ?? 'error de conexión';
                    console.warn('[realtime] error de conexión:', error);
                });

                pendientes.splice(0).forEach((callback) => callback());
            } catch (e) {
                window.__cafeRoseUltimoError = e.message;
                console.warn('[realtime] No se pudo iniciar WebSocket:', e);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', iniciar);
        } else {
            iniciar();
        }
    })();
</script>
@endif
