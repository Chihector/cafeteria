<?php

namespace App\Http\Controllers;

use App\Models\Producto;

class CartaController extends Controller
{
    public function index()
    {
        $productos = Producto::where('disponible', true)
            ->where(function ($query) {
                $query->where('controlar_stock', false)
                    ->orWhere('stock', '>', 0);
            })
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        return view('menu.index', compact('productos'));
    }
}
