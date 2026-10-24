<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'categoria',
        'disponible',
        'imagen',
        'destacado',
        'vegetariano',
        'alergenos',
        'controlar_stock',
        'stock',
        'stock_minimo',
    ];

    protected $casts = [
        'alergenos' => 'array',
        'destacado' => 'boolean',
        'vegetariano' => 'boolean',
        'disponible' => 'boolean',
        'controlar_stock' => 'boolean',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class);
    }

    /**
     * Indica si el producto necesita control de stock y ya está en el mínimo.
     */
    public function stockBajo(): bool
    {
        return $this->controlar_stock && $this->stock <= $this->stock_minimo;
    }

    /**
     * Productos que se controlan y están en o bajo el mínimo configurado.
     */
    public function scopeConStockBajo($query)
    {
        return $query->where('controlar_stock', true)
            ->whereColumn('stock', '<=', 'stock_minimo');
    }
}
