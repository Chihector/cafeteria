<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Café Rosé</title>
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
</head>
<body class="bg-[#111318] flex items-center justify-center min-h-screen font-sans text-white relative overflow-hidden">
    <!-- Background element -->
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1600&h=900&fit=crop&auto=format')] bg-cover bg-center opacity-10"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-[#111318] via-[#111318]/80 to-[#111318]/50"></div>

    <div class="w-full max-w-md p-8 bg-[#1A1D25]/90 backdrop-blur-md rounded-3xl border border-white/10 shadow-2xl relative z-10 mx-4">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-terracotta rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4 shadow-lg shadow-terracotta/20">☕</div>
            <h1 class="text-2xl font-black tracking-tight text-white mb-1">Café Rosé</h1>
            <p class="text-white/50 text-sm">Ingresa tus credenciales para continuar</p>
        </div>

        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
                <p class="text-red-400 text-sm font-semibold">{{ $errors->first() }}</p>
            </div>
        @endif

        <form action="/login" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-white/60 text-xs font-bold mb-2 uppercase tracking-wide">Correo Electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-[#111318] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-terracotta focus:ring-1 focus:ring-terracotta transition-colors"
                    placeholder="admin@test.com">
            </div>

            <div>
                <label class="block text-white/60 text-xs font-bold mb-2 uppercase tracking-wide">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full bg-[#111318] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-terracotta focus:ring-1 focus:ring-terracotta transition-colors"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between mt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-white/20 bg-[#111318] text-terracotta focus:ring-terracotta focus:ring-offset-0">
                    <span class="text-white/60 text-sm font-medium">Recordarme</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-terracotta hover:bg-terracotta-dark text-white font-bold py-3.5 rounded-xl transition-all active:scale-95 shadow-lg shadow-terracotta/20 mt-4 text-base">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-8 text-center border-t border-white/5 pt-6">
            <p class="text-white/30 text-xs">Credenciales de prueba (password: <span class="text-white/50 font-mono">password</span>)</p>
            <p class="text-white/40 text-xs mt-1 font-mono">admin@test.com · barista@test.com · cocina@test.com</p>
        </div>
    </div>
</body>
</html>
