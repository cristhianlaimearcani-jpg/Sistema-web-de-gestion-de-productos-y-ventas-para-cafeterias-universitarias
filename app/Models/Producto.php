<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    /** categoría => ícono */
    public const CATEGORIAS = [
        'Bebidas calientes' => '☕',
        'Bebidas frías' => '🥤',
        'Sándwiches' => '🥪',
        'Comidas' => '🍔',
        'Postres' => '🍰',
        'Snacks' => '🍪',
        'Combos' => '🍽️',
    ];

    /** Con esta cantidad o menos se avisa "stock bajo". */
    public const STOCK_BAJO = 5;

    protected $fillable = ['nombre', 'descripcion', 'precio', 'categoria', 'disponible', 'imagen', 'stock'];

    protected $attributes = ['stock' => 20];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'disponible' => 'boolean',
            'stock' => 'integer',
        ];
    }

    /** No se puede pedir: lo marcaron como agotado o ya no quedan unidades. */
    public function getAgotadoAttribute(): bool
    {
        return ! $this->disponible || (int) $this->stock <= 0;
    }

    public function getStockBajoAttribute(): bool
    {
        return ! $this->agotado && (int) $this->stock <= self::STOCK_BAJO;
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function scopeDisponibles($consulta)
    {
        return $consulta->where('disponible', true)->where('stock', '>', 0);
    }

    /** Palabras del nombre => ícono. El primero que coincida gana; si ninguno, el de la categoría. */
    private const ICONOS = [
        'espresso' => '☕', 'café' => '☕', 'cafe' => '☕', 'capuchino' => '☕', 'latte' => '☕', 'moka' => '☕',
        'chocolate caliente' => '☕', 'té' => '🍵', 'té helado' => '🧋', 'mate' => '🍵', 'api con' => '🥣',
        'frapp' => '🧋', 'smoothie' => '🥤', 'batido' => '🥤', 'limonada' => '🍋', 'naranja' => '🍊',
        'papaya' => '🥭', 'durazno' => '🍑', 'mocochinchi' => '🍑', 'agua' => '💧', 'refresco' => '🥤', 'gaseosa' => '🥤',
        'hamburguesa' => '🍔', 'hot dog' => '🌭', 'pizza' => '🍕', 'pasta' => '🍝', 'ensalada' => '🥗',
        'salchipapa' => '🍟', 'papas' => '🍟', 'nuggets' => '🍗', 'pollo' => '🍗', 'silpancho' => '🥩',
        'almuerzo' => '🍽️', 'sopa' => '🍲',
        'croissant' => '🥐', 'tostado' => '🥪', 'sándwich' => '🥪', 'sandwich' => '🥪',
        'cheesecake' => '🍰', 'torta' => '🍰', 'brownie' => '🍫', 'muffin' => '🧁', 'flan' => '🍮',
        'gelatina' => '🍮', 'helado' => '🍨', 'alfajor' => '🍪', 'galleta' => '🍪', 'dona' => '🍩',
        'salteña' => '🥟', 'empanada' => '🥟', 'tucumana' => '🥟', 'nachos' => '🌮', 'chips' => '🥔',
        'frutos secos' => '🥜', 'fruta' => '🍎',
    ];

    public function getIconoAttribute(): string
    {
        $nombre = mb_strtolower($this->nombre ?? '');
        $iconos = self::ICONOS;
        // Las palabras más largas (más específicas) se revisan primero: "frappé de café" antes que "café".
        uksort($iconos, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($iconos as $palabra => $icono) {
            if (str_contains($nombre, $palabra)) {
                return $icono;
            }
        }

        return self::CATEGORIAS[$this->categoria] ?? '🍽️';
    }

    /** Clase CSS de color según la categoría (ej. cat-bebidas-calientes). */
    public function getCategoriaClaseAttribute(): string
    {
        return 'cat-'.\Illuminate\Support\Str::slug($this->categoria ?? '');
    }

    public function getImagenUrlAttribute(): ?string
    {
        if ($this->imagen) {
            return asset('productos/'.$this->imagen);
        }

        // Foto suelta: public/productos/<nombre-del-producto>.jpg (jpg, jpeg, png o webp)
        $nombre = $this->nombre_archivo_foto;
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file(public_path('productos'.DIRECTORY_SEPARATOR.$nombre.'.'.$ext))) {
                return asset('productos/'.$nombre.'.'.$ext);
            }
        }

        // Ilustración incluida con el sistema (public/productos/ilustraciones/<nombre>.webp)
        if (is_file(public_path('productos/ilustraciones/'.$nombre.'.webp'))) {
            return asset('productos/ilustraciones/'.$nombre.'.webp');
        }

        // Producto nuevo sin foto: ilustración genérica de su categoría
        $categoria = \Illuminate\Support\Str::slug($this->categoria ?? '');
        if ($categoria !== '' && is_file(public_path('productos/ilustraciones/categoria-'.$categoria.'.webp'))) {
            return asset('productos/ilustraciones/categoria-'.$categoria.'.webp');
        }

        return null;
    }

    /** true si el producto tiene una foto propia (subida o en public/productos), no una ilustración. */
    public function getTieneFotoAttribute(): bool
    {
        $url = $this->imagen_url;

        return $url !== null && ! str_contains($url, 'productos/ilustraciones/');
    }

    /** Nombre de archivo (sin extensión) que se busca como foto, ej. cafe-americano. */
    public function getNombreArchivoFotoAttribute(): string
    {
        return \Illuminate\Support\Str::slug($this->nombre ?? '');
    }

    /** Ilustración de una categoría (para pestañas y títulos del menú). */
    public static function imagenDeCategoria(?string $categoria): ?string
    {
        $slug = \Illuminate\Support\Str::slug($categoria ?? '');
        if ($slug !== '' && is_file(public_path('productos/ilustraciones/categoria-'.$slug.'.webp'))) {
            return asset('productos/ilustraciones/categoria-'.$slug.'.webp');
        }

        return null;
    }

    /** Los productos más pedidos (o los primeros disponibles si aún no hay pedidos). */
    public static function destacados(int $cantidad = 8)
    {
        $ids = DetallePedido::query()
            ->selectRaw('producto_id, SUM(cantidad) as vendidos')
            ->groupBy('producto_id')
            ->orderByDesc('vendidos')
            ->limit($cantidad * 2)
            ->pluck('producto_id')
            ->all();

        $lista = self::query()->disponibles()->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids))->values();

        if ($lista->count() < $cantidad) {
            $extra = self::query()->disponibles()->whereNotIn('id', $lista->pluck('id'))
                ->orderBy('id')->limit($cantidad - $lista->count())->get();
            $lista = $lista->concat($extra);
        }

        return $lista->take($cantidad)->values();
    }
}
