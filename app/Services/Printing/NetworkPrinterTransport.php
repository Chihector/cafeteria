<?php

namespace App\Services\Printing;

use RuntimeException;

class NetworkPrinterTransport implements PrinterTransport
{
    public function __construct(
        private readonly ?string $host,
        private readonly int $port = 9100,
        private readonly float $timeout = 3.0,
    ) {}

    public function send(string $payload): void
    {
        if (blank($this->host)) {
            throw new RuntimeException('No hay una impresora configurada (PRINTER_HOST).');
        }

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            $this->timeout,
        );

        if ($socket === false) {
            throw new RuntimeException("No se pudo conectar con la impresora {$this->host}:{$this->port} ({$errstr}).");
        }

        stream_set_timeout($socket, (int) ceil($this->timeout));

        try {
            $total = strlen($payload);
            $enviados = 0;

            while ($enviados < $total) {
                $escritos = @fwrite($socket, substr($payload, $enviados));
                if ($escritos === false || $escritos === 0) {
                    throw new RuntimeException('La impresora cerró la conexión antes de recibir la comanda completa.');
                }
                $enviados += $escritos;
            }
        } finally {
            fclose($socket);
        }
    }

    public function isReachable(): bool
    {
        if (blank($this->host)) {
            return false;
        }

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
