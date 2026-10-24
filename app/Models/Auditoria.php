<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $fillable = [
        'user_id',
        'accion',
        'modulo',
        'descripcion',
        'modelo_type',
        'modelo_id',
        'cambios',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'cambios' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function modelo(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Registra un evento de auditoría, omitiendo datos sensibles.
     */
    public static function registrar(
        string $accion,
        string $modulo,
        string $descripcion,
        ?Model $modelo = null,
        array $cambios = [],
    ): self {
        return self::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => Str::limit($descripcion, 250),
            'modelo_type' => $modelo?->getMorphClass(),
            'modelo_id' => $modelo?->getKey(),
            'cambios' => self::limpiarCambios($cambios) ?: null,
            'ip' => Request::ip(),
            'user_agent' => Str::limit((string) Request::userAgent(), 500, ''),
        ]);
    }

    /**
     * Quita contraseñas, tokens y timestamps internos de los cambios guardados.
     */
    private static function limpiarCambios(array $cambios): array
    {
        unset($cambios['password'], $cambios['remember_token'], $cambios['updated_at'], $cambios['created_at']);

        return $cambios;
    }
}
