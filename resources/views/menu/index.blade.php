<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Café Rosé - Carta Digital</title>
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
                        terracotta: {
                            DEFAULT: '#E83E8C',
                            dark: '#C2185B',
                            light: '#F48FB1',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-pink-50" x-data="{ searchQuery: '' }">

<div class="h-screen w-full flex items-center justify-center overflow-hidden">
    <div class="relative h-full w-full max-w-[390px] bg-white overflow-hidden shadow-2xl flex flex-col">
        
        <!-- Hero header -->
        <div class="relative bg-[#3E2723] pt-12 pb-5 px-5 flex-none">
            <div class="absolute inset-0 overflow-hidden">
                <img src="https://images.unsplash.com/photo-1497935586351-b67a49e012bf?w=800&h=400&fit=crop&auto=format" alt="" class="w-full h-full object-cover opacity-30" />
                <div class="absolute inset-0 bg-gradient-to-b from-[#3E2723]/60 via-[#3E2723]/80 to-[#3E2723]"></div>
            </div>
            <div class="relative">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 bg-terracotta rounded-2xl flex items-center justify-center shadow-lg">
                        <span class="text-2xl">☕</span>
                    </div>
                    <div>
                        <p class="text-white font-black text-xl leading-tight">Café Rosé</p>
                        <p class="text-white/70 text-xs">Carta digital · Temporada {{ date('Y') }}</p>
                    </div>
                </div>
                <div class="inline-flex items-center gap-1.5 bg-terracotta/20 border border-terracotta/30 rounded-full px-3 py-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-terracotta"></span>
                    <p class="text-terracotta-light text-xs font-medium">Estás viendo la carta digital</p>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="px-5 pt-4 pb-2 bg-white flex-none">
            <div class="relative">
                <input type="text" x-model="searchQuery" placeholder="Buscar en la carta..." class="w-full bg-pink-50 border border-pink-100 rounded-2xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-terracotta/30 transition-all">
                <span class="absolute left-4 top-3 text-pink-400 text-lg">🔍</span>
            </div>
        </div>

        <!-- Filters (Simulated UI) -->
        <div class="px-4 pt-2 pb-2 bg-white border-b border-pink-100 flex-none">
            <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-0.5">
                <a href="#coffee" class="flex-none flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap bg-pink-50 text-pink-600 hover:bg-pink-100 transition-all">
                    <span>☕</span><span>Cafés</span>
                </a>
                <a href="#pastries" class="flex-none flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap bg-pink-50 text-pink-600 hover:bg-pink-100 transition-all">
                    <span>🥐</span><span>Pastelería</span>
                </a>
                <a href="#sandwiches" class="flex-none flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap bg-pink-50 text-pink-600 hover:bg-pink-100 transition-all">
                    <span>🥪</span><span>Sándwiches</span>
                </a>
                <a href="#cold_drinks" class="flex-none flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap bg-pink-50 text-pink-600 hover:bg-pink-100 transition-all">
                    <span>🥤</span><span>Bebidas Frías</span>
                </a>
            </div>
        </div>

        <!-- Menu content -->
        <div class="flex-1 overflow-y-auto scrollbar-hide pb-28">
            @php
                $catLabels = [
                    'coffee' => '☕ Cafés',
                    'pastries' => '🥐 Pastelería',
                    'sandwiches' => '🥪 Sándwiches',
                    'cold_drinks' => '🥤 Bebidas Frías'
                ];
            @endphp
            
            @if($productos->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <span class="text-4xl mb-3">🔍</span>
                    <p class="font-bold text-gray-700">Sin resultados</p>
                    <p class="text-gray-400 text-sm">El menú no está disponible.</p>
                </div>
            @endif

            @foreach($productos as $categoria => $items)
                @if(isset($catLabels[$categoria]))
                    @php
                        $catSearchText = strtolower(addslashes($items->pluck('nombre')->merge($items->pluck('descripcion'))->implode(' ')));
                    @endphp
                    <div id="{{ $categoria }}" x-show="searchQuery === '' || '{{ $catSearchText }}'.includes(searchQuery.toLowerCase())">
                        <div class="px-5 pt-5 pb-2 flex items-center gap-2">
                            <h2 class="font-black text-gray-800 text-base">{{ $catLabels[$categoria] }}</h2>
                            <span class="text-xs text-pink-400 font-medium">{{ count($items) }} opciones</span>
                        </div>
                        <div class="px-4 space-y-3">
                            @foreach($items as $item)
                                @php
                                    $itemSearchText = strtolower(addslashes($item->nombre . ' ' . $item->descripcion));
                                @endphp
                                <div class="bg-white border border-pink-100 rounded-2xl overflow-hidden shadow-sm flex gap-3"
                                     x-show="searchQuery === '' || '{{ $itemSearchText }}'.includes(searchQuery.toLowerCase())">
                                    <img src="{{ $item->imagen ?? 'https://via.placeholder.com/150' }}" alt="{{ $item->nombre }}" class="w-24 h-24 object-cover flex-none bg-pink-50" />
                                    <div class="py-3 pr-3 flex flex-col justify-between flex-1 min-w-0">
                                        <div>
                                            <div class="flex items-start justify-between gap-1 mb-0.5">
                                                <p class="font-bold text-gray-800 text-sm leading-tight">{{ $item->nombre }}</p>
                                                @if($item->destacado)
                                                    <span class="text-[10px] bg-pink-100 text-pink-700 font-bold px-1.5 py-0.5 rounded-full flex-none">💖</span>
                                                @endif
                                            </div>
                                            <p class="text-gray-500 text-xs leading-tight line-clamp-2">{{ $item->descripcion }}</p>
                                        </div>
                                        <div>
                                            @if($item->alergenos && count($item->alergenos) > 0)
                                                <div class="flex flex-wrap gap-1 mb-1.5">
                                                    @foreach($item->alergenos as $a)
                                                        <span class="text-[9px] bg-pink-50 text-pink-500 px-1.5 py-0.5 rounded font-medium">{{ $a }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="flex items-center justify-between mt-1">
                                                <span class="font-black text-terracotta text-base">${{ number_format($item->precio, 0, ',', '.') }}</span>
                                                @if($item->vegetariano)
                                                    <span class="text-[10px] bg-green-50 text-green-600 font-bold px-2 py-0.5 rounded-full border border-green-200">Veggie</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Footer -->
        <div class="absolute bottom-0 left-0 right-0 bg-white border-t border-pink-100 px-5 py-4">
            <p class="text-center text-pink-400 text-xs font-medium">Para ordenar, habla con tu barista</p>
            <p class="text-center text-gray-300 text-[10px] mt-0.5">Precios incluyen IVA · cafe-rose.cl</p>
        </div>
        
    </div>
</div>

</body>
</html>
