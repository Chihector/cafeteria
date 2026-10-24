<?php

namespace App\Services\Printing;

interface PrinterTransport
{
    /**
     * Envía bytes crudos a la impresora.
     *
     * @throws \RuntimeException
     */
    public function send(string $payload): void;

    /**
     * Comprueba si el puerto de la impresora responde.
     */
    public function isReachable(): bool;
}
