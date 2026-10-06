<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // [nombre, descripción, precio en Bs, disponible]
        $catalogo = [
            'Desayunos' => [
                ['Api con pastel',            'Api morado caliente con pastel',            10, true],
                ['Pan con queso',             'Pan fresco con queso derretido',             5, true],
                ['Huevos revueltos con pan',  'Huevos revueltos con pan y mantequilla',    12, true],
                ['Tostadas con mermelada',    'Dos tostadas con mermelada de frutilla',     6, true],
            ],
            'Salteñas y empanadas' => [
                ['Salteña',                   'Salteña jugosa de pollo o carne',            8, true],
                ['Empanada de queso',         'Empanada frita con queso',                   6, true],
                ['Tucumana de pollo',         'Empanada tucumana rellena de pollo',         7, true],
                ['Papa rellena',              'Papa rellena con carne y huevo',             7, true],
            ],
            'Sándwiches y hamburguesas' => [
                ['Sándwich de pollo',         'Sándwich con pollo y verduras',             12, true],
                ['Sándwich de jamón y queso', 'Pan con jamón, queso y tomate',             10, true],
                ['Hamburguesa clásica',       'Carne, lechuga, tomate y papas fritas',     18, true],
                ['Hamburguesa con queso',     'Doble queso y papas fritas',                20, true],
            ],
            'Almuerzos' => [
                ['Milanesa de pollo',         'Milanesa con arroz, papas y ensalada',      20, true],
                ['Pollo broaster',            'Presa de pollo broaster con papas fritas',  22, true],
                ['Silpancho',                 'Silpancho con arroz, papas y huevo',        22, true],
                ['Sopa de maní',              'Sopa de maní con papas fritas',             10, true],
            ],
            'Bebidas calientes' => [
                ['Café',                      'Café recién preparado',                      5, true],
                ['Té con leche',              'Té caliente con leche',                      4, false],
                ['Chocolate caliente',        'Chocolate caliente espeso',                  8, true],
                ['Mate de coca',              'Mate de coca caliente',                      3, true],
            ],
            'Bebidas frías' => [
                ['Jugo de naranja',           'Jugo natural',                               7, true],
                ['Limonada',                  'Limonada fresca',                            6, true],
                ['Mocochinchi',               'Refresco tradicional de durazno',            5, true],
                ['Refresco embotellado',      'Gaseosa de 500 ml',                          6, true],
                ['Agua mineral',              'Botella de 500 ml',                          4, true],
            ],
            'Postres' => [
                ['Gelatina',                  'Gelatina de sabores',                        4, true],
                ['Porción de torta',          'Porción de torta de chocolate',             10, true],
                ['Brownie',                   'Brownie con nueces',                         8, false],
                ['Helado',                    'Helado de crema, una bola',                  6, true],
            ],
        ];

        foreach ($catalogo as $categoria => $productos) {
            foreach ($productos as [$nombre, $descripcion, $precio, $disponible]) {
                Producto::updateOrCreate(
                    ['nombre' => $nombre],
                    [
                        'categoria'   => $categoria,
                        'descripcion' => $descripcion,
                        'precio'      => $precio,
                        'disponible'  => $disponible,
                    ]
                );
            }
        }
    }
}