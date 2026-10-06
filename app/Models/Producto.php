<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    // Secciones del menú (en este orden) con su ícono
    public const CATEGORIAS = [
        'Desayunos'                 => '🥐',
        'Salteñas y empanadas'      => '🥟',
        'Sándwiches y hamburguesas' => '🥪',
        'Almuerzos'                 => '🍛',
        'Bebidas calientes'         => '☕',
        'Bebidas frías'             => '🥤',
        'Postres'                   => '🍰',
        'Otros'                     => '🍽️',
    ];

    protected $fillable = ['nombre', 'categoria', 'descripcion', 'precio', 'imagen', 'disponible'];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'disponible' => 'boolean',
        ];
    }

    public function getIconoAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? '🍽️';
    }
}