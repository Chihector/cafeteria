<?php

namespace App\Services\Printing;

class PrintResult
{
    private function __construct(
        public readonly bool $attempted,
        public readonly bool $ok,
        public readonly ?string $error = null,
    ) {}

    public static function ok(): self
    {
        return new self(true, true);
    }

    public static function fallida(string $error): self
    {
        return new self(true, false, $error);
    }

    public static function omitida(?string $motivo = null): self
    {
        return new self(false, false, $motivo);
    }

    /**
     * @return array{attempted: bool, ok: bool, error: ?string, fallback: bool}
     */
    public function toArray(): array
    {
        return [
            'attempted' => $this->attempted,
            'ok' => $this->ok,
            'error' => $this->error,
            'fallback' => ! $this->ok,
        ];
    }
}
