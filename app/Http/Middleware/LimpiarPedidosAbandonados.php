<?php

namespace App\Http\Middleware;

use App\Models\Pedido;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Como mucho una vez por minuto, cancela los pedidos que quedaron sin pagar
 * (así su stock vuelve al menú). Nunca rompe la página si algo falla.
 */
class LimpiarPedidosAbandonados
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Cache::add('cafeqr:limpieza-pedidos', 1, 60) && Schema::hasColumn('pedidos', 'stock_reservado')) {
                Pedido::cancelarAbandonados();
            }
        } catch (\Throwable $e) {
            Log::warning('CafeQR: no se pudo revisar pedidos abandonados: '.$e->getMessage());
        }

        return $next($request);
    }
}
