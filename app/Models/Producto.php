<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'precio', 'categoria', 'disponible', 'imagen', 'destacado', 'vegetariano', 'alergenos'];

    protected $casts = [
        'alergenos' => 'array',
        'destacado' => 'boolean',
        'vegetariano' => 'boolean',
        'disponible' => 'boolean',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }
}
