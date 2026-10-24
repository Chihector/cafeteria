<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\ImpresoraController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Redirección inicial según el rol del usuario
Route::get('/', [AuthController::class, 'home']);

// Vista Carta Digital (Pública)
Route::get('/carta', [CartaController::class, 'index']);

// Rutas Protegidas
Route::middleware('auth')->group(function () {

    /*
     |------------------------------------------------------------------
     | Administración (solo Administrador)
     |------------------------------------------------------------------
     */
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard']);

        // CRUD Productos (sin show: no existe vista de detalle)
        Route::resource('productos', ProductController::class)->except(['show']);

        // Cierre de Turno / Reporte Z
        Route::get('/cierre-turno', [AdminController::class, 'cierreTurno'])->name('admin.cierre-turno');
        Route::get('/cierre-turno/csv', [AdminController::class, 'cierreTurnoCsv'])->name('admin.cierre-turno.csv');

        // Inventario
        Route::get('/inventario', [InventarioController::class, 'index'])->name('admin.inventario');
        Route::post('/inventario/{producto}/entrada', [InventarioController::class, 'entrada'])->name('admin.inventario.entrada');
        Route::post('/inventario/{producto}/ajuste', [InventarioController::class, 'ajuste'])->name('admin.inventario.ajuste');

        // Auditoría
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('admin.auditoria');

        // Impresora térmica de red (Fase 13)
        Route::get('/impresora', [ImpresoraController::class, 'index'])->name('admin.impresora');
        Route::post('/impresora/probar', [ImpresoraController::class, 'probar'])->name('admin.impresora.probar');
        Route::post('/impresora/comprobar', [ImpresoraController::class, 'comprobar'])->name('admin.impresora.comprobar');
    });

    /*
     |------------------------------------------------------------------
     | Punto de Venta (Administrador y Barista)
     |------------------------------------------------------------------
     */
    Route::middleware('role:admin,barista')->group(function () {
        Route::get('/pos', [PosController::class, 'index']);

        // Pedidos
        Route::post('/api/pedidos', [PedidoController::class, 'store']);
        Route::post('/api/pedidos/{pedido}/pagar', [PedidoController::class, 'pagar']);
        Route::get('/api/pedidos/{pedido}/ticket', [PedidoController::class, 'ticket'])->name('pedidos.ticket');

        // Datos del POS
        Route::get('/api/mesas', [PosController::class, 'getMesas']);
        Route::get('/api/productos', [PosController::class, 'getProductos']);
    });

    /*
     |------------------------------------------------------------------
     | Cocina / KDS (Administrador y Cocina)
     |------------------------------------------------------------------
     */
    Route::middleware('role:admin,cocina')->group(function () {
        Route::view('/kds', 'kds.index');

        Route::get('/api/kds/pedidos', [PedidoController::class, 'getPedidosCocina']);
        Route::patch('/api/kds/pedidos/{pedido}/estado', [PedidoController::class, 'updateEstado']);
    });

    /*
     |------------------------------------------------------------------
     | Comanda de cocina (todos los roles operativos pueden reimprimir)
     |------------------------------------------------------------------
     */
    Route::middleware('role:admin,barista,cocina')->group(function () {
        Route::get('/api/pedidos/{pedido}/comanda', [PedidoController::class, 'comanda'])->name('pedidos.comanda');
    });
});
