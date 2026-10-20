<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin - Café Rosé')</title>
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
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col md:flex-row">

    <!-- Sidebar -->
    <div class="w-full md:w-64 bg-[#111318] text-white flex flex-col flex-none">
        <div class="p-6 border-b border-white/5 flex items-center gap-3">
            <div class="w-10 h-10 bg-terracotta rounded-xl flex items-center justify-center font-bold text-xl shadow-lg shadow-terracotta/20">☕</div>
            <div>
                <h1 class="font-black tracking-tight leading-tight">Café Rosé</h1>
                <p class="text-white/40 text-xs">Admin Panel</p>
            </div>
        </div>
        
        <div class="p-4 flex-1">
            <a href="/admin" class="flex items-center gap-3 {{ request()->is('admin') ? 'bg-terracotta/10 text-terracotta' : 'text-white/60 hover:text-white hover:bg-white/5' }} px-4 py-3 rounded-xl font-bold mb-2 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                Dashboard
            </a>
            <a href="/admin/productos" class="flex items-center gap-3 {{ request()->is('admin/productos*') ? 'bg-terracotta/10 text-terracotta' : 'text-white/60 hover:text-white hover:bg-white/5' }} px-4 py-3 rounded-xl font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                Productos
            </a>
        </div>
        
        <div class="p-6 border-t border-white/5 flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-sm font-bold">
                    {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                </div>
                <div>
                    <p class="font-bold text-sm line-clamp-1">{{ Auth::user()->name ?? 'Administrador' }}</p>
                    <p class="text-white/40 text-xs">{{ Auth::user()->roleLabel() ?? 'Administrador' }}</p>
                </div>
            </div>
            
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-6 md:p-10 overflow-y-auto">
        @yield('content')
    </div>
</body>
</html>
