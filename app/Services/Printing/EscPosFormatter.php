<?php

namespace App\Services\Printing;

use App\Models\Pedido;
use Illuminate\Support\Str;

/**
 * Genera comandos ESC/POS para impresoras térmicas de red (Epson, Xprinter, etc.).
 */
class EscPosFormatter
{
    public function __construct(private readonly int $width = 42) {}

    public function comanda(Pedido $pedido): string
    {
        $pedido->loadMissing(['mesa', 'garzon', 'detalles.producto']);

        $salida = $this->inicializar();
        $salida .= $this->centrar('CAFE ROSE', true);
        $salida .= $this->centrar('COMANDA DE COCINA');
        $salida .= $this->linea(str_repeat('=', $this->width));
        $salida .= $this->linea('Pedido: #'.$pedido->id);
        $salida .= $this->linea('Mesa: '.($pedido->mesa->numero ?? 'N/A'));
        $salida .= $this->linea('Barista: '.($pedido->garzon->name ?? 'N/A'));
        $salida .= $this->linea('Fecha: '.$pedido->created_at->format('d/m/Y H:i'));
        $salida .= $this->linea(str_repeat('-', $this->width));

        foreach ($pedido->detalles as $detalle) {
            $linea = $detalle->cantidad.'x '.$detalle->producto->nombre;
            foreach ($this->partir($linea, $this->width) as $parte) {
                $salida .= $this->linea($parte);
            }

            if (filled($detalle->notas_especiales)) {
                foreach ($this->partir('   > '.$detalle->notas_especiales, $this->width) as $nota) {
                    $salida .= $this->linea($nota);
                }
            }
        }

        $salida .= $this->linea(str_repeat('-', $this->width));

        if (filled($pedido->observaciones)) {
            $salida .= $this->linea('Observaciones:');
            foreach ($this->partir($pedido->observaciones, $this->width) as $parte) {
                $salida .= $this->linea($parte);
            }
            $salida .= $this->linea(str_repeat('-', $this->width));
        }

        $salida .= $this->centrar('EN PREPARACION');
        $salida .= $this->linea('Impreso: '.now()->format('d/m/Y H:i:s'));
        $salida .= $this->finalizar();

        return $salida;
    }

    public function ticket(Pedido $pedido): string
    {
        $pedido->loadMissing(['mesa', 'garzon', 'detalles.producto']);

        $salida = $this->inicializar();
        $salida .= $this->centrar('CAFE ROSE', true);
        $salida .= $this->centrar('Av. Providencia 1234, Local 5');
        $salida .= $this->centrar('Santiago, Chile');
        $salida .= $this->linea(str_repeat('=', $this->width));
        $salida .= $this->linea('Mesa: '.($pedido->mesa->numero ?? 'N/A').'   Ticket: #'.$pedido->id);
        $salida .= $this->linea('Fecha: '.$pedido->created_at->format('d/m/Y H:i'));
        $salida .= $this->linea('Barista: '.($pedido->garzon->name ?? 'N/A'));
        $salida .= $this->linea(str_repeat('-', $this->width));
        $salida .= $this->linea($this->dosColumnas('CANT x DESCRIPCION', 'TOTAL'));
        $salida .= $this->linea(str_repeat('-', $this->width));

        foreach ($pedido->detalles as $detalle) {
            $descripcion = $detalle->cantidad.'x '.$detalle->producto->nombre;
            $importe = '$'.number_format((float) $detalle->cantidad * (float) $detalle->precio_unitario, 0, ',', '.');
            foreach ($this->partir($descripcion, max(1, $this->width - strlen($importe) - 1)) as $indice => $parte) {
                $salida .= $this->linea($indice === 0
                    ? $this->dosColumnas($parte, $importe)
                    : '   '.$parte);
            }

            if (filled($detalle->notas_especiales)) {
                foreach ($this->partir('   > '.$detalle->notas_especiales, $this->width) as $nota) {
                    $salida .= $this->linea($nota);
                }
            }
        }

        $salida .= $this->linea(str_repeat('-', $this->width));
        $salida .= $this->linea($this->dosColumnas('Subtotal:', $this->dinero($pedido->total)));

        if ((float) $pedido->propina > 0) {
            $salida .= $this->linea($this->dosColumnas('Propina:', $this->dinero($pedido->propina)));
        }

        $total = (float) $pedido->total + (float) $pedido->propina;
        $salida .= "\x1B\x45\x01".$this->linea($this->dosColumnas('TOTAL:', $this->dinero($total)))."\x1B\x45\x00";
        $salida .= $this->linea('Pago: '.ucfirst((string) $pedido->metodo_pago));
        $salida .= $this->linea(str_repeat('=', $this->width));
        $salida .= $this->centrar('Gracias por su visita');
        $salida .= $this->centrar('Vuelva pronto a Cafe Rose');
        $salida .= $this->finalizar();

        return $salida;
    }

    public function prueba(): string
    {
        $salida = $this->inicializar();
        $salida .= $this->centrar('CAFE ROSE', true);
        $salida .= $this->centrar('PRUEBA DE IMPRESORA');
        $salida .= $this->linea(str_repeat('=', $this->width));
        $salida .= $this->linea('Si puedes leer este texto, la impresora');
        $salida .= $this->linea('de red esta configurada correctamente.');
        $salida .= $this->linea('');
        $salida .= $this->linea('Ancho configurado: '.$this->width.' columnas');
        $salida .= $this->linea('Fecha: '.now()->format('d/m/Y H:i:s'));
        $salida .= $this->linea(str_repeat('=', $this->width));
        $salida .= $this->finalizar();

        return $salida;
    }

    private function inicializar(): string
    {
        // ESC @ inicializa; ESC t 2 selecciona CP850 (multilingüe).
        return "\x1B\x40\x1B\x74\x02";
    }

    private function finalizar(): string
    {
        // Avanza papel y corta (GS V 0).
        return "\n\n\n\x1D\x56\x00";
    }

    private function dinero(mixed $monto): string
    {
        return '$'.number_format((float) $monto, 0, ',', '.');
    }

    /**
     * Convierte UTF-8 a CP850 para que tildes y "ñ" salgan bien en la térmica.
     */
    private function texto(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);

        // ASCII primero: evita que impresoras baratas impriman basura con tildes.
        $texto = Str::ascii($texto);
        $convertido = @iconv('UTF-8', 'CP850//TRANSLIT//IGNORE', $texto);

        if ($convertido !== false) {
            return $convertido;
        }

        return $texto;
    }

    private function linea(string $texto = ''): string
    {
        return $this->texto($texto)."\n";
    }

    private function centrar(string $texto, bool $doble = false): string
    {
        $texto = $this->texto($texto);
        $ancho = $doble ? intdiv($this->width, 2) : $this->width;
        $espacios = max(0, intdiv(max(0, $ancho - strlen($texto)), 2));
        $comando = $doble ? "\x1D\x21\x11" : '';

        return $comando.str_repeat(' ', $espacios).$texto.($doble ? "\x1D\x21\x00" : '')."\n";
    }

    private function dosColumnas(string $izquierda, string $derecha): string
    {
        $izquierda = $this->texto($izquierda);
        $derecha = $this->texto($derecha);
        $espacio = max(1, $this->width - strlen($izquierda) - strlen($derecha));

        return $izquierda.str_repeat(' ', $espacio).$derecha;
    }

    /**
     * @return list<string>
     */
    private function partir(string $texto, int $ancho): array
    {
        $texto = $this->texto(trim($texto));
        if ($texto === '') {
            return [''];
        }

        $partes = [];
        foreach (explode("\n", $texto) as $linea) {
            foreach (str_split($linea, max(1, $ancho)) as $parte) {
                $partes[] = $parte;
            }
        }

        return $partes;
    }
}
