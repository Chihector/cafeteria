<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
