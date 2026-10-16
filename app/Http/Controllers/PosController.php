<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Producto;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        return view('pos.index');
    }

    public function getMesas()
    {
        $mesas = Mesa::with(['pedidos' => function ($q) {
            $q->whereIn('estado', ['pendiente', 'preparando', 'listo', 'entregado'])->latest()->with('garzon');
        }])->orderBy('numero')->get();

        $mesas->map(function ($mesa) {
            if ($mesa->estado === 'ocupada' && $mesa->pedidos->isNotEmpty()) {
                $pedido = $mesa->pedidos->first();
                $mesa->pedido_id = $pedido->id;
                $mesa->total = $pedido->total;
                $mesa->barista = $pedido->garzon ? $pedido->garzon->name : 'Desconocido';
                $mesa->tiempo_ocupada = $pedido->created_at->diffForHumans();
            }
            return $mesa;
        });

        return response()->json($mesas);
    }

    public function getProductos()
    {
        return response()->json(Producto::where('disponible', true)->get());
    }
}
