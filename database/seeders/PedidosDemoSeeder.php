<?php

namespace Database\Seeders;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PedidosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::latest('id')->first();

        if (! $user) {
            $this->command->warn('Primero registra un usuario en /registro.');
            return;
        }

        $productos = Producto::where('disponible', true)->get();

        if ($productos->count() < 3) {
            $this->command->warn('Faltan productos. Ejecuta: php artisan db:seed');
            return;
        }

        $estados = ['entregado', 'entregado', 'listo', 'pagado'];

        foreach ($estados as $i => $estado) {
            $pedido = new Pedido([
                'user_id'       => $user->id,
                'estado'        => $estado,
                'codigo_recojo' => strtoupper(Str::random(6)),
                'hora_recojo'   => '10:' . str_pad((string) ($i * 10), 2, '0', STR_PAD_LEFT) . ':00',
            ]);
            $pedido->created_at = now()->subDays(($i + 1) * 2);
            $pedido->save();

            $total = 0;

            foreach ($productos->random(2) as $producto) {
                $cantidad = rand(1, 3);

                DetallePedido::create([
                    'pedido_id'       => $pedido->id,
                    'producto_id'     => $producto->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $producto->precio,
                ]);

                $total += $cantidad * $producto->precio;
            }

            $pedido->update(['total' => $total]);
        }

        $this->command->info('Se crearon 4 pedidos de prueba para ' . $user->email);
    }
}