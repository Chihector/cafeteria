<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda #{{ $pedido->id }} - Café Rosé</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'JetBrains Mono', monospace;
            background-color: #ffffff;
            color: #000000;
            margin: 0;
            padding: 4px;
            font-size: 11px;
            line-height: 1.2;
        }
        .comanda {
            width: 72mm;
            max-width: 72mm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
            letter-spacing: 1px;
        }
        .header .subtitulo {
            font-size: 10px;
            margin: 2px 0;
        }
        .datos {
            margin-bottom: 6px;
            font-size: 10px;
        }
        .datos div {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
        }
        .datos .label {
            font-weight: bold;
        }
        .separador {
            border-bottom: 1px dashed #000;
            margin: 4px 0;
        }
        .columnas {
            display: flex;
            font-size: 10px;
            margin-bottom: 2px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }
        .col-cant { width: 20%; }
        .col-producto { width: 60%; }
        .col-notas { width: 20%; }
        .item {
            display: flex;
            font-size: 10px;
            margin: 2px 0;
        }
        .item .cant {
            width: 20%;
            font-weight: bold;
        }
        .item .producto {
            width: 60%;
        }
        .item .notas {
            width: 20%;
            font-size: 9px;
            color: #b00;
            font-weight: bold;
        }
        .notas-especiales {
            background: #ffe6e6;
            border: 1px solid #b00;
            padding: 2px 4px;
            margin: 2px 0;
            font-size: 9px;
        }
        .footer {
            margin-top: 8px;
            border-top: 2px solid #000;
            padding-top: 4px;
            font-size: 10px;
            text-align: center;
        }
        .estado {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 6px;
            margin: 4px 0;
            font-weight: bold;
            font-size: 12px;
        }

        @media print {
            body {
                padding: 0;
                background: none;
            }
            .no-print {
                display: none !important;
            }
            .comanda {
                width: 100%;
                max-width: none;
                padding: 0;
            }
        }

        @page {
            size: 72mm auto;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="comanda">
        <div class="header">
            <h1>☕ Café Rosé</h1>
            <div class="subtitulo">COMANDA DE COCINA</div>
        </div>

        <div class="datos">
            <div><span class="label">Comanda:</span> <span>#{{ $pedido->id }}</span></div>
            <div><span class="label">Mesa:</span> <span>{{ $pedido->mesa->numero ?? 'N/A' }}</span></div>
            <div><span class="label">Barista:</span> <span>{{ $pedido->garzon ? $pedido->garzon->name : 'N/A' }}</span></div>
            <div><span class="label">Hora:</span> <span>{{ \Carbon\Carbon::parse($pedido->created_at)->format('d/m/Y H:i') }}</span></div>
        </div>

        <div class="separador"></div>

        <div class="columnas">
            <span class="col-cant">CANT</span>
            <span class="col-producto">PRODUCTO</span>
            <span class="col-notas">NOTAS</span>
        </div>

        @foreach($pedido->detalles as $detalle)
        <div class="item">
            <span class="cant">{{ $detalle->cantidad }}x</span>
            <span class="producto">{{ Str::limit($detalle->producto->nombre, 28) }}</span>
            @if($detalle->notas_especiales)
            <span class="notas">{{ $detalle->notas_especiales }}</span>
            @else
            <span class="notas">-</span>
            @endif
        </div>
        @if($detalle->notas_especiales)
        <div class="notas-especiales">⚠ {{ $detalle->notas_especiales }}</div>
        @endif
        @endforeach

        <div class="separador"></div>

        <div class="estado">EN PREPARACIÓN</div>

        <div class="footer">
            <div>Café Rosé - Sistema de Cocina</div>
            <div>Impreso: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
            <div class="no-print">
                <button onclick="window.print()" style="background:#000;color:#fff;border:none;padding:6px 12px;border-radius:4px;font-weight:bold;cursor:pointer;font-family:'JetBrains Mono',monospace;font-size:10px;">
                    🖨️ Imprimir Comanda
                </button>
                <button onclick="window.close()" style="background:#eee;color:#333;border:none;padding:6px 12px;border-radius:4px;font-weight:bold;cursor:pointer;font-family:'JetBrains Mono',monospace;font-size:10px;margin-left:6px;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Impresión automática al abrir (ideal para impresora térmica de cocina)
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 400);
        };
    </script>
</body>
</html>