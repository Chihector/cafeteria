<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class CartaController extends Controller
{
    public function index()
    {
        $productos = Producto::where('disponible', true)
                             ->orderBy('categoria')
                             ->orderBy('nombre')
                             ->get()
                             ->groupBy('categoria');
                             
        return view('menu.index', compact('productos'));
    }
}
