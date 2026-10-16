<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Mesa;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $hoy = Carbon::today();
        
        $ventasHoy = Pedido::whereDate('created_at', $hoy)
                           ->whereNotIn('estado', ['pendiente', 'preparando'])
                           ->sum('total');
                           
        $pedidosHoy = Pedido::whereDate('created_at', $hoy)->count();
        $mesasActivas = Mesa::where('estado', 'ocupada')->count();
        $productosTotales = Producto::count();
        
        // El enlace a la carta para generar el QR
        $urlCarta = url('/carta');
        
        return view('admin.dashboard', compact('ventasHoy', 'pedidosHoy', 'mesasActivas', 'productosTotales', 'urlCarta'));
    }

    public function cierreTurno(Request $request)
    {
        $periodo = $request->input('periodo', 'dia'); // dia | mes | anio
        $valor   = $request->input('valor', now()->toDateString());

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

        $pedidos = $query->orderBy('created_at', 'desc')->get();

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
        $etiqueta = match ($periodo) {
            'dia' => \Carbon\Carbon::parse($valor)->format('d/m/Y'),
            'mes' => \Carbon\Carbon::parse($valor.'-01')->format('m/Y'),
            'anio' => $valor,
            default => \Carbon\Carbon::parse($valor)->format('d/m/Y'),
        };

        return view('admin.cierre-turno', compact(
            'periodo', 'valor', 'etiqueta', 'pedidos', 'totalVentas', 'totalPropinas',
            'totalGeneral', 'cantidadPedidos', 'desglose'
        ));
    }
}
