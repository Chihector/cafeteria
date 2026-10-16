<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\AuthController;

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Redirección inicial
Route::get('/', function () {
    return redirect('/admin');
});

// Vista Carta Digital (Pública)
Route::get('/carta', [CartaController::class, 'index']);

// Rutas Protegidas
Route::middleware('auth')->group(function () {
    
    // Vista KDS
    Route::get('/kds', function () {
        return view('kds.index');
    });

    // Vista POS
    Route::get('/pos', [PosController::class, 'index']);

    // Rutas Admin
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard']);
        
        // CRUD Productos
        Route::resource('productos', \App\Http\Controllers\Admin\ProductController::class);
        
        // Cierre de Turno / Reporte Z
        Route::get('/cierre-turno', [AdminController::class, 'cierreTurno'])->name('admin.cierre-turno');
    });

    // Rutas de API
    Route::prefix('api')->group(function () {
        // Pedidos
        Route::post('/pedidos', [PedidoController::class, 'store']);
        Route::get('/kds/pedidos', [PedidoController::class, 'getPedidosCocina']);
        Route::patch('/kds/pedidos/{pedido}/estado', [PedidoController::class, 'updateEstado']);
        Route::post('/pedidos/{pedido}/pagar', [PedidoController::class, 'pagar']);
        Route::get('/pedidos/{pedido}/ticket', [PedidoController::class, 'ticket'])->name('pedidos.ticket');
        
        // POS Data
        Route::get('/mesas', [PosController::class, 'getMesas']);
        Route::get('/productos', [PosController::class, 'getProductos']);
        
        // Comanda de cocina (impresión térmica)
        Route::get('/pedidos/{pedido}/comanda', [PedidoController::class, 'comanda'])->name('pedidos.comanda');
    });
});
