<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Estadísticas del negocio (solo administrador). */
class EstadisticaController extends Controller
{
    public const PERIODOS = [
        'hoy' => 'Hoy',
        '7' => 'Últimos 7 días',
        '30' => 'Últimos 30 días',
        'mes' => 'Este mes',
        'anio' => 'Este año',
        'todo' => 'Desde el inicio',
    ];

    private const DIAS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    public function index(Request $request): View
    {
        [$desde, $hasta, $periodo, $titulo] = $this->rango($request);

        $pagados = Pedido::whereNotNull('pagado_en')->whereBetween('pagado_en', [$desde, $hasta])
            ->with(['detalles.producto', 'user'])->get();
        $ventas = $pagados->where('estado', '!=', Pedido::CANCELADO)->values();

        $resumen = $this->resumen($ventas, $desde, $hasta);

        // Mismo largo de tiempo justo antes, para comparar.
        $anterior = null;
        if ($periodo !== 'todo') {
            $largo = $desde->diffInSeconds($hasta);
            $antHasta = $desde->copy()->subSecond();
            $antDesde = $antHasta->copy()->subSeconds($largo);
            $antVentas = Pedido::whereNotNull('pagado_en')->whereBetween('pagado_en', [$antDesde, $antHasta])
                ->where('estado', '!=', Pedido::CANCELADO)->get();
            $anterior = ['ventas' => (float) $antVentas->sum('total'), 'pedidos' => $antVentas->count()];
        }

        $detalles = $ventas->flatMap->detalles;

        return view('admin.estadisticas', [
            'periodo' => $periodo,
            'titulo' => $titulo,
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => $resumen,
            'anterior' => $anterior,
            'serie' => $this->serieTiempo($ventas, $desde, $hasta),
            'porHora' => $this->porHora($ventas),
            'porDia' => $this->porDiaSemana($ventas),
            'categorias' => $this->porCategoria($detalles),
            'productos' => $this->porProducto($detalles),
            'sinVentas' => Producto::whereNotIn('id', $detalles->pluck('producto_id')->filter()->unique())
                ->orderBy('categoria')->orderBy('nombre')->get(),
            'metodos' => $this->porMetodo($ventas),
            'clientes' => $this->mejoresClientes($ventas),
            'cupones' => $ventas->whereNotNull('cupon')->groupBy('cupon')->map(fn ($g, $c) => [
                'codigo' => $c, 'usos' => $g->count(), 'descuento' => (float) $g->sum('descuento'),
            ])->sortByDesc('usos')->values(),
            'cancelaciones' => $this->cancelaciones($desde, $hasta),
            'estadosActuales' => Pedido::where('estado', '!=', Pedido::CARRITO)
                ->selectRaw('estado, count(*) as cantidad')->groupBy('estado')->pluck('cantidad', 'estado'),
            'stockBajo' => Producto::orderBy('stock')->orderBy('nombre')->limit(8)->get(),
            'soporte' => [
                'nuevos' => Ticket::whereBetween('created_at', [$desde, $hasta])->count(),
                'pendientes' => Ticket::pendientes(),
                'resueltos' => Ticket::whereBetween('resuelto_en', [$desde, $hasta])->count(),
            ],
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string, 3: string} */
    private function rango(Request $request): array
    {
        $ahora = now();
        $d = $request->query('desde');
        $h = $request->query('hasta');

        if ($d && $h && strtotime($d) && strtotime($h)) {
            $desde = Carbon::parse($d)->startOfDay();
            $hasta = Carbon::parse($h)->endOfDay();
            if ($desde->gt($hasta)) {
                [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
            }

            return [$desde, $hasta, 'rango', $desde->format('d/m/Y').' – '.$hasta->format('d/m/Y')];
        }

        $periodo = array_key_exists((string) $request->query('periodo'), self::PERIODOS) ? (string) $request->query('periodo') : '30';

        $desde = match ($periodo) {
            'hoy' => $ahora->copy()->startOfDay(),
            '7' => $ahora->copy()->subDays(6)->startOfDay(),
            '30' => $ahora->copy()->subDays(29)->startOfDay(),
            'mes' => $ahora->copy()->startOfMonth(),
            'anio' => $ahora->copy()->startOfYear(),
            'todo' => Carbon::parse(Pedido::min('created_at') ?? $ahora)->startOfDay(),
        };

        return [$desde, $ahora->copy()->endOfDay(), $periodo, self::PERIODOS[$periodo]];
    }

    private function resumen(Collection $ventas, Carbon $desde, Carbon $hasta): array
    {
        $total = (float) $ventas->sum('total');
        $n = $ventas->count();

        $minutos = fn (string $hastaCampo) => $ventas->filter(fn ($p) => $p->pagado_en && $p->{$hastaCampo})
            ->map(fn ($p) => $p->pagado_en->diffInMinutes($p->{$hastaCampo}))
            ->avg();

        return [
            'ventas' => $total,
            'pedidos' => $n,
            'ticket' => $n ? $total / $n : 0,
            'unidades' => (int) $ventas->flatMap->detalles->sum('cantidad'),
            'clientes' => $ventas->pluck('user_id')->unique()->count(),
            'nuevos' => User::where('rol', User::ROL_CLIENTE)->whereBetween('created_at', [$desde, $hasta])->count(),
            'descuentos' => (float) $ventas->sum('descuento'),
            'hastaListo' => $minutos('listo_en'),
            'hastaEntrega' => $minutos('entregado_en'),
            'entregados' => $ventas->where('estado', Pedido::ENTREGADO)->count(),
        ];
    }

    /** Ventas por día (o por mes si el periodo es largo). */
    private function serieTiempo(Collection $ventas, Carbon $desde, Carbon $hasta): array
    {
        $porMes = $desde->diffInDays($hasta) > 62;
        $puntos = [];
        $cursor = $porMes ? $desde->copy()->startOfMonth() : $desde->copy()->startOfDay();

        while ($cursor->lte($hasta)) {
            $clave = $porMes ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $puntos[$clave] = [
                'etiqueta' => $porMes ? ucfirst($cursor->translatedFormat('M y')) : $cursor->format('d/m'),
                'ventas' => 0.0,
                'pedidos' => 0,
            ];
            $porMes ? $cursor->addMonth() : $cursor->addDay();
            if (count($puntos) > 400) {
                break;
            }
        }

        foreach ($ventas as $p) {
            $clave = $p->pagado_en->timezone(config('app.timezone'))->format($porMes ? 'Y-m' : 'Y-m-d');
            if (isset($puntos[$clave])) {
                $puntos[$clave]['ventas'] += (float) $p->total;
                $puntos[$clave]['pedidos']++;
            }
        }

        return ['porMes' => $porMes, 'puntos' => array_values($puntos)];
    }

    private function porHora(Collection $ventas): array
    {
        $horas = array_fill(0, 24, ['pedidos' => 0, 'ventas' => 0.0]);
        foreach ($ventas as $p) {
            $h = (int) $p->pagado_en->timezone(config('app.timezone'))->format('G');
            $horas[$h]['pedidos']++;
            $horas[$h]['ventas'] += (float) $p->total;
        }

        // Se muestra de 6:00 a 22:00, más cualquier otra hora que tenga ventas.
        $salida = [];
        foreach ($horas as $h => $dato) {
            if (($h >= 6 && $h <= 22) || $dato['pedidos'] > 0) {
                $salida[] = ['etiqueta' => str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00', 'hora' => $h] + $dato;
            }
        }

        return $salida;
    }

    private function porDiaSemana(Collection $ventas): array
    {
        $dias = [];
        foreach (self::DIAS as $i => $d) {
            $dias[$i] = ['etiqueta' => $d, 'pedidos' => 0, 'ventas' => 0.0];
        }
        foreach ($ventas as $p) {
            $i = (int) $p->pagado_en->timezone(config('app.timezone'))->format('N') - 1;
            $dias[$i]['pedidos']++;
            $dias[$i]['ventas'] += (float) $p->total;
        }

        return array_values($dias);
    }

    private function porCategoria(Collection $detalles): Collection
    {
        $total = (float) $detalles->sum(fn ($d) => $d->subtotal);

        return $detalles->groupBy(fn ($d) => $d->producto?->categoria ?? 'Otros')
            ->map(fn ($g, $cat) => [
                'categoria' => $cat,
                'unidades' => (int) $g->sum('cantidad'),
                'ingresos' => (float) $g->sum(fn ($d) => $d->subtotal),
                'porcentaje' => $total > 0 ? $g->sum(fn ($d) => $d->subtotal) / $total * 100 : 0,
            ])->sortByDesc('ingresos')->values();
    }

    private function porProducto(Collection $detalles): Collection
    {
        return $detalles->groupBy(fn ($d) => $d->producto_id ?: 'x-'.$d->nombre)
            ->map(fn ($g) => [
                'nombre' => $g->first()->producto?->nombre ?? $g->first()->nombre,
                'categoria' => $g->first()->producto?->categoria ?? '—',
                'unidades' => (int) $g->sum('cantidad'),
                'ingresos' => (float) $g->sum(fn ($d) => $d->subtotal),
                'stock' => $g->first()->producto?->stock,
            ])->sortByDesc('unidades')->values();
    }

    private function porMetodo(Collection $ventas): array
    {
        $total = max(1, $ventas->count());
        $res = [];
        foreach (['qr' => 'Pago con QR', 'efectivo' => 'Efectivo'] as $m => $texto) {
            $g = $ventas->where('metodo_pago', $m);
            $res[] = [
                'metodo' => $m, 'texto' => $texto, 'pedidos' => $g->count(),
                'ventas' => (float) $g->sum('total'), 'porcentaje' => $g->count() / $total * 100,
            ];
        }

        return $res;
    }

    private function mejoresClientes(Collection $ventas): Collection
    {
        return $ventas->groupBy('user_id')->map(fn ($g) => [
            'nombre' => $g->first()->user?->name ?? 'Cliente eliminado',
            'correo' => $g->first()->user?->email,
            'pedidos' => $g->count(),
            'gasto' => (float) $g->sum('total'),
        ])->sortByDesc('gasto')->take(5)->values();
    }

    private function cancelaciones(Carbon $desde, Carbon $hasta): array
    {
        $creados = Pedido::where('estado', '!=', Pedido::CARRITO)->whereBetween('created_at', [$desde, $hasta])->count();
        $cancelados = Pedido::where('estado', Pedido::CANCELADO)->whereBetween('cancelado_en', [$desde, $hasta])->get();

        return [
            'cantidad' => $cancelados->count(),
            'porcentaje' => $creados > 0 ? $cancelados->count() / $creados * 100 : 0,
            'porCliente' => $cancelados->where('cancelado_por', 'cliente')->count(),
            'porPersonal' => $cancelados->where('cancelado_por', 'personal')->count(),
            'porSistema' => $cancelados->where('cancelado_por', 'sistema')->count(),
            'reembolsosPendientes' => Pedido::where('reembolso_pendiente', true)->count(),
            'montoPendiente' => (float) Pedido::where('reembolso_pendiente', true)->sum('total'),
            'reembolsados' => Pedido::whereBetween('reembolsado_en', [$desde, $hasta])->count(),
            'montoReembolsado' => (float) Pedido::whereBetween('reembolsado_en', [$desde, $hasta])->sum('total'),
        ];
    }
}
