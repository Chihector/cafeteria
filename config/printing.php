<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Impresión física de comandas y tickets (Fase 13)
    |--------------------------------------------------------------------------
    |
    | La aplicación envía comandos ESC/POS directamente a una impresora
    | térmica de red (puerto 9100 por defecto). El servidor Laravel debe
    | estar en la misma red local que la impresora. Si está desactivado,
    | el POS mantiene la impresión por navegador como respaldo.
    |
    */

    'enabled' => (bool) env('PRINTER_ENABLED', false),

    'auto_print' => (bool) env('PRINTER_AUTO_PRINT', true),

    'host' => env('PRINTER_HOST'),

    'port' => (int) env('PRINTER_PORT', 9100),

    'timeout' => (float) env('PRINTER_TIMEOUT', 3),

    // 42 columnas para 80 mm y 32 columnas para 58 mm.
    'width' => (int) env('PRINTER_WIDTH', 42),

];
