<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - Pedido #{{ $pedido->id }}</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'JetBrains Mono', monospace;
            background-color: #f3f4f6;
            color: #111827;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .ticket {
            background-color: #ffffff;
            width: 300px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .text-lg { font-size: 1.125rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .border-t { border-top: 1px dashed #9ca3af; }
        .border-b { border-bottom: 1px dashed #9ca3af; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        
        @media print {
            body { background: none; padding: 0; align-items: flex-start; justify-content: flex-start; }
            .ticket { box-shadow: none; border: none; width: 100%; max-width: 80mm; padding: 0; margin: 0 auto; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="text-center mb-4">
            <h1 class="text-lg font-bold">Café Rosé</h1>
            <p class="text-xs">Av. Providencia 1234, Local 5</p>
            <p class="text-xs">Santiago, Chile</p>
        </div>

        <div class="border-t border-b py-2 mb-4 text-sm">
            <div class="flex justify-between mb-1">
                <span>Mesa: <strong>{{ $pedido->mesa->numero }}</strong></span>
                <span>Ticket: <strong>#{{ $pedido->id }}</strong></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Fecha:</span>
                <span>{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Barista:</span>
                <span>{{ $pedido->garzon ? $pedido->garzon->name : 'N/A' }}</span>
            </div>
        </div>

        <div class="mb-4 text-sm">
            <div class="flex justify-between font-bold border-b pb-1 mb-2">
                <span>CANT x DESC</span>
                <span>TOTAL</span>
            </div>
            
            @foreach($pedido->detalles as $detalle)
            <div class="flex justify-between mb-1">
                <span>{{ $detalle->cantidad }}x {{ Str::limit($detalle->producto->nombre, 15) }}</span>
                <span>${{ number_format($detalle->cantidad * $detalle->precio_unitario, 0, ',', '.') }}</span>
            </div>
            @if($detalle->notas_especiales)
            <div class="text-xs ml-4 mb-1">- {{ $detalle->notas_especiales }}</div>
            @endif
            @endforeach
        </div>

        <div class="border-t pt-2 mb-4">
            <div class="flex justify-between text-sm mb-1">
                <span>Subtotal:</span>
                <span>${{ number_format($pedido->total, 0, ',', '.') }}</span>
            </div>
            @if($pedido->propina > 0)
            <div class="flex justify-between text-sm mb-1">
                <span>Propina (10%):</span>
                <span>${{ number_format($pedido->propina, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between font-bold text-lg mt-2">
                <span>TOTAL:</span>
                <span>${{ number_format($pedido->total + $pedido->propina, 0, ',', '.') }}</span>
            </div>
        </div>
        
        <div class="text-center text-xs mt-4">
            <p class="mb-1">Pago: <strong>{{ ucfirst($pedido->metodo_pago) }}</strong></p>
            <p class="mt-4">¡Gracias por su visita!</p>
            <p class="mt-1">Vuelva pronto a Café Rosé ☕</p>
        </div>
        
        <div class="text-center mt-6 no-print">
            <button onclick="window.print()" style="background: #E83E8C; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; font-family: 'JetBrains Mono', monospace;">
                🖨️ Imprimir Ticket
            </button>
            <button onclick="window.close()" style="background: #e5e7eb; color: #374151; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; font-family: 'JetBrains Mono', monospace; margin-left: 10px;">
                Cerrar
            </button>
        </div>
    </div>
    
    <script>
        // Imprimir automáticamente al abrir (opcional)
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
