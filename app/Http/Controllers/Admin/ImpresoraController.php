<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Services\Printing\PrinterService;

class ImpresoraController extends Controller
{
    public function index(PrinterService $impresora)
    {
        return view('admin.impresora', [
            'habilitada' => $impresora->habilitada(),
            'configurada' => $impresora->configurada(),
            'host' => config('printing.host'),
            'puerto' => config('printing.port'),
            'ancho' => config('printing.width'),
            'timeout' => config('printing.timeout'),
            'autoPrint' => (bool) config('printing.auto_print'),
        ]);
    }

    public function probar(PrinterService $impresora)
    {
        $resultado = $impresora->probar();

        Auditoria::registrar(
            'probar',
            'impresora',
            $resultado->ok
                ? 'Prueba de impresión enviada correctamente'
                : 'Prueba de impresión fallida: '.($resultado->error ?? 'sin detalle'),
        );

        if ($resultado->ok) {
            return back()->with('success', 'Prueba enviada a la impresora. Revisa el papel físico.');
        }

        return back()->with('error', 'No se pudo imprimir: '.($resultado->error ?? 'revisa la configuración.'));
    }

    public function comprobar(PrinterService $impresora)
    {
        if (! $impresora->habilitada()) {
            return back()->with('error', 'La impresión física está desactivada (PRINTER_ENABLED=false).');
        }

        if (! $impresora->configurada()) {
            return back()->with('error', 'Falta configurar PRINTER_HOST con la IP de la impresora.');
        }

        if ($impresora->alcanzable()) {
            return back()->with('success', 'La impresora responde en el puerto configurado.');
        }

        return back()->with('error', 'La impresora no responde. Revisa IP, puerto y que esté encendida.');
    }
}
