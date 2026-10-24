<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminController extends Controller
{
    public function dashboard()
    {
        $hoy = Carbon::today();

        // Solo los pedidos cobrados cuentan como venta, igual que en el Reporte Z.
        $ventasHoy = Pedido::whereDate('created_at', $hoy)
            ->where('estado', 'pagado')
            ->sum('total');

        $pedidosHoy = Pedido::whereDate('created_at', $hoy)->count();
        $mesasActivas = Mesa::where('estado', 'ocupada')->count();
        $productosTotales = Producto::count();
        $productosStockBajo = Producto::conStockBajo()->count();

        // El enlace a la carta para generar el QR
        $urlCarta = url('/carta');

        return view('admin.dashboard', compact(
            'ventasHoy',
            'pedidosHoy',
            'mesasActivas',
            'productosTotales',
            'productosStockBajo',
            'urlCarta',
        ));
    }

    public function cierreTurno(Request $request)
    {
        $periodo = $this->validarPeriodo($request);
        $valor = $request->input('valor', now()->toDateString());

        $pedidos = $this->pedidosDelPeriodo($periodo, $valor);

        $totalVentas = $pedidos->sum('total');
        $totalPropinas = $pedidos->sum('propina');
        $totalGeneral = $totalVentas + $totalPropinas;
        $cantidadPedidos = $pedidos->count();

        $metodos = ['efectivo', 'tarjeta', 'transferencia'];
        $desglose = [];
        foreach ($metodos as $metodo) {
            $grupo = $pedidos->where('metodo_pago', $metodo);
            $desglose[$metodo] = [
                'monto' => $grupo->sum('total'),
                'propinas' => $grupo->sum('propina'),
                'cantidad' => $grupo->count(),
                'total_con_propina' => $grupo->sum('total') + $grupo->sum('propina'),
            ];
        }

        // Etiqueta amigable para mostrar en pantalla
        $etiqueta = $this->etiquetaPeriodo($periodo, $valor);

        return view('admin.cierre-turno', compact(
            'periodo', 'valor', 'etiqueta', 'pedidos', 'totalVentas', 'totalPropinas',
            'totalGeneral', 'cantidadPedidos', 'desglose'
        ));
    }

    /**
     * Fase 10: descarga del Reporte Z en formato CSV para contabilidad externa.
     */
    public function cierreTurnoCsv(Request $request)
    {
        $periodo = $this->validarPeriodo($request);
        $valor = $request->input('valor', now()->toDateString());

        $pedidos = $this->pedidosDelPeriodo($periodo, $valor);
        $etiqueta = $this->etiquetaPeriodo($periodo, $valor);
        $lineas = $this->construirLineasCsv($pedidos, $periodo, $etiqueta);

        $nombreArchivo = sprintf('reporte-z-%s-%s.csv', $periodo, str_replace('-', '', $valor));

        return response()->streamDownload(
            function () use ($lineas) {
                $salida = fopen('php://output', 'w');
                // BOM UTF-8 para que Excel detecte bien las tildes y la "ñ"
                fwrite($salida, "\xEF\xBB\xBF");
                foreach ($lineas as $fila) {
                    fputcsv($salida, $fila, ';', '"', '\\', "\r\n");
                }
                fclose($salida);
            },
            $nombreArchivo,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }

    /**
     * Valida el período solicitado; cualquier valor no soportado cae a "dia".
     */
    private function validarPeriodo(Request $request): string
    {
        return in_array($request->input('periodo'), ['dia', 'mes', 'anio'], true)
            ? $request->input('periodo')
            : 'dia';
    }

    /**
     * Pedidos pagados del período solicitado, ordenados del más reciente al más antiguo.
     */
    private function pedidosDelPeriodo(string $periodo, string $valor): Collection
    {
        $query = Pedido::with(['mesa', 'garzon', 'detalles.producto'])
            ->where('estado', 'pagado');

        // Filtro escalable por día / mes / año
        match ($periodo) {
            'dia' => $query->whereDate('created_at', $valor),
            'mes' => $query->whereYear('created_at', substr($valor, 0, 4))
                ->whereMonth('created_at', substr($valor, 5, 2)),
            'anio' => $query->whereYear('created_at', $valor),
            default => $query->whereDate('created_at', now()->toDateString()),
        };

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Etiqueta legible del período para mostrar en pantalla y en el CSV.
     */
    private function etiquetaPeriodo(string $periodo, string $valor): string
    {
        return match ($periodo) {
            'dia' => Carbon::parse($valor)->format('d/m/Y'),
            'mes' => Carbon::parse($valor.'-01')->format('m/Y'),
            'anio' => $valor,
            default => Carbon::parse($valor)->format('d/m/Y'),
        };
    }

    /**
     * Construye las filas del CSV del Reporte Z (resumen, desglose y detalle).
     * Separador ";", decimales con coma y miles con punto (formato español para Excel).
     */
    private function construirLineasCsv(Collection $pedidos, string $periodo, string $etiqueta): array
    {
        $totalVentas = $pedidos->sum('total');
        $totalPropinas = $pedidos->sum('propina');
        $totalGeneral = $totalVentas + $totalPropinas;
        $cantidadPedidos = $pedidos->count();

        $metodosLabels = [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
        ];

        $lineas = [];
        $lineas[] = ['Café Rosé - Reporte Z'];
        $lineas[] = ['Período', ucfirst($periodo).' '.$etiqueta];
        $lineas[] = ['Generado', now()->format('d/m/Y H:i')];
        $lineas[] = [];

        $lineas[] = ['RESUMEN'];
        $lineas[] = ['Ventas Netas', $this->formatoDinero($totalVentas)];
        $lineas[] = ['Propinas', $this->formatoDinero($totalPropinas)];
        $lineas[] = ['Total a Cuadrar', $this->formatoDinero($totalGeneral)];
        $lineas[] = ['Pedidos Pagados', (string) $cantidadPedidos];
        $lineas[] = [];

        $lineas[] = ['DESGLOSE POR MÉTODO DE PAGO'];
        $lineas[] = ['Método', 'Monto', 'Propinas', 'Pedidos', 'Total con Propina'];
        foreach (array_keys($metodosLabels) as $metodo) {
            $grupo = $pedidos->where('metodo_pago', $metodo);
            $lineas[] = [
                $metodosLabels[$metodo],
                $this->formatoDinero($grupo->sum('total')),
                $this->formatoDinero($grupo->sum('propina')),
                (string) $grupo->count(),
                $this->formatoDinero($grupo->sum('total') + $grupo->sum('propina')),
            ];
        }
        $lineas[] = [];

        $lineas[] = ['DETALLE DE PEDIDOS PAGADOS'];
        $lineas[] = ['Nº Pedido', 'Fecha', 'Hora', 'Mesa', 'Barista', 'Método de Pago', 'Total', 'Propina', 'Total con Propina'];
        foreach ($pedidos as $pedido) {
            $lineas[] = [
                (string) $pedido->id,
                Carbon::parse($pedido->created_at)->format('d/m/Y'),
                Carbon::parse($pedido->created_at)->format('H:i'),
                'Mesa '.($pedido->mesa->numero ?? 'N/A'),
                $pedido->garzon->name ?? 'Desconocido',
                $metodosLabels[$pedido->metodo_pago] ?? ucfirst((string) $pedido->metodo_pago),
                $this->formatoDinero($pedido->total),
                $this->formatoDinero($pedido->propina),
                $this->formatoDinero($pedido->total + $pedido->propina),
            ];
        }
        $lineas[] = [
            'TOTAL', '', '', '', '', '',
            $this->formatoDinero($totalVentas),
            $this->formatoDinero($totalPropinas),
            $this->formatoDinero($totalGeneral),
        ];

        return $lineas;
    }

    /**
     * Dinero en formato español para Excel: miles con punto y decimales con coma.
     */
    private function formatoDinero(mixed $monto): string
    {
        return number_format((float) $monto, 2, ',', '.');
    }
}
