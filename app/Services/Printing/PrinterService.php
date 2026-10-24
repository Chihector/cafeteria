<?php

namespace App\Services\Printing;

use App\Models\Pedido;
use Illuminate\Support\Facades\Log;
use Throwable;

class PrinterService
{
    public function __construct(private readonly PrinterTransport $transport) {}

    public function habilitada(): bool
    {
        return (bool) config('printing.enabled');
    }

    public function configurada(): bool
    {
        return filled(config('printing.host'));
    }

    public function comandaAutomatica(Pedido $pedido): PrintResult
    {
        if (! config('printing.auto_print')) {
            return PrintResult::omitida('La impresión automática está desactivada.');
        }

        return $this->imprimirComanda($pedido);
    }

    public function ticketAutomatico(Pedido $pedido): PrintResult
    {
        if (! config('printing.auto_print')) {
            return PrintResult::omitida('La impresión automática está desactivada.');
        }

        return $this->imprimirTicket($pedido);
    }

    public function imprimirComanda(Pedido $pedido): PrintResult
    {
        return $this->enviar($this->formatter()->comanda($pedido));
    }

    public function imprimirTicket(Pedido $pedido): PrintResult
    {
        return $this->enviar($this->formatter()->ticket($pedido));
    }

    public function probar(): PrintResult
    {
        return $this->enviar($this->formatter()->prueba());
    }

    public function alcanzable(): bool
    {
        if (! $this->habilitada() || ! $this->configurada()) {
            return false;
        }

        return $this->transport->isReachable();
    }

    private function enviar(string $payload): PrintResult
    {
        if (! $this->habilitada()) {
            return PrintResult::omitida('La impresión física está desactivada.');
        }

        if (! $this->configurada()) {
            return PrintResult::fallida('Falta configurar PRINTER_HOST.');
        }

        try {
            $this->transport->send($payload);

            return PrintResult::ok();
        } catch (Throwable $e) {
            Log::warning('No se pudo imprimir en la térmica de red.', [
                'host' => config('printing.host'),
                'port' => config('printing.port'),
                'error' => $e->getMessage(),
            ]);

            return PrintResult::fallida($e->getMessage());
        }
    }

    private function formatter(): EscPosFormatter
    {
        return new EscPosFormatter((int) config('printing.width', 42));
    }
}
