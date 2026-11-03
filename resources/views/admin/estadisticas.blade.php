@extends('layouts.app')

@section('titulo', 'Estadísticas')

@section('contenido')
    @php
        $bs = fn ($v) => 'Bs '.number_format($v, 2);
        $cambio = function ($actual, $antes) {
            if ($antes === null) { return null; }
            if ($antes == 0) { return $actual > 0 ? ['texto' => 'nuevo', 'sube' => true] : null; }
            $p = ($actual - $antes) / $antes * 100;
            return ['texto' => ($p >= 0 ? '+' : '').number_format($p, 0).'%', 'sube' => $p >= 0];
        };
        $cVentas = $cambio($resumen['ventas'], $anterior['ventas'] ?? null);
        $cPedidos = $cambio($resumen['pedidos'], $anterior['pedidos'] ?? null);
        $min = fn ($m) => $m === null ? '—' : (round($m) < 60 ? round($m).' min' : floor($m / 60).' h '.(round($m) % 60).' min');
    @endphp

    <div class="cabecera-pagina">
        <div>
            <h1>Estadísticas del negocio</h1>
            <p class="subtitulo" style="margin:0">{{ $titulo }} · del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}. Se cuentan los pedidos pagados que no se cancelaron.</p>
        </div>
    </div>

    <div class="est-filtros">
        <nav class="filtro-estados" aria-label="Periodo">
            @foreach (\App\Http\Controllers\EstadisticaController::PERIODOS as $clave => $texto)
                <a href="{{ route('admin.estadisticas', ['periodo' => $clave]) }}" class="{{ $periodo === (string) $clave ? 'activo' : '' }}">{{ $texto }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('admin.estadisticas') }}" class="est-rango">
            <label>Desde <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" required></label>
            <label>Hasta <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" required></label>
            <button type="submit" class="btn chico">Ver</button>
        </form>
    </div>

    <div class="kpis est-kpis">
        <div class="kpi verde-kpi">
            <div class="num">{{ $bs($resumen['ventas']) }}</div>
            <div class="rotulo">Ventas @if ($cVentas)<span class="cambio {{ $cVentas['sube'] ? 'sube' : 'baja' }}">{{ $cVentas['sube'] ? '▲' : '▼' }} {{ $cVentas['texto'] }}</span> vs. periodo anterior @endif</div>
        </div>
        <div class="kpi">
            <div class="num">{{ $resumen['pedidos'] }}</div>
            <div class="rotulo">Pedidos pagados @if ($cPedidos)<span class="cambio {{ $cPedidos['sube'] ? 'sube' : 'baja' }}">{{ $cPedidos['sube'] ? '▲' : '▼' }} {{ $cPedidos['texto'] }}</span>@endif</div>
        </div>
        <div class="kpi"><div class="num">{{ $bs($resumen['ticket']) }}</div><div class="rotulo">Ticket promedio por pedido</div></div>
        <div class="kpi"><div class="num">{{ $resumen['unidades'] }}</div><div class="rotulo">Productos vendidos (unidades)</div></div>
        <div class="kpi"><div class="num">{{ $resumen['clientes'] }}</div><div class="rotulo">Clientes que compraron · {{ $resumen['nuevos'] }} {{ $resumen['nuevos'] === 1 ? 'nuevo registrado' : 'nuevos registrados' }}</div></div>
        <div class="kpi"><div class="num">{{ $min($resumen['hastaListo']) }}</div><div class="rotulo">Tiempo promedio del pago a “listo”</div></div>
        <div class="kpi"><div class="num">{{ $bs($resumen['descuentos']) }}</div><div class="rotulo">Descuentos por cupones</div></div>
        <div class="kpi {{ $cancelaciones['cantidad'] ? 'alerta-kpi' : '' }}"><div class="num">{{ $cancelaciones['cantidad'] }}</div><div class="rotulo">Cancelaciones ({{ number_format($cancelaciones['porcentaje'], 0) }}% de los pedidos)</div></div>
    </div>

    <section class="tarjeta">
        <div class="est-cab">
            <h2>Ventas {{ $serie['porMes'] ? 'por mes' : 'por día' }}</h2>
            <span class="ayuda">Pasa el mouse (o toca) una barra para ver el detalle.</span>
        </div>
        @if ($resumen['pedidos'])
            @include('admin._barras', [
                'titulo' => 'Ventas por '.($serie['porMes'] ? 'mes' : 'día'),
                'puntos' => collect($serie['puntos'])->map(fn ($p) => ['etiqueta' => $p['etiqueta'], 'valor' => $p['ventas'], 'tip' => $p['etiqueta'].': Bs '.number_format($p['ventas'], 2).' · '.$p['pedidos'].' '.($p['pedidos'] === 1 ? 'pedido' : 'pedidos')])->all(),
            ])
        @else
            <p class="ayuda">No hay ventas en este periodo.</p>
        @endif
    </section>

    <div class="columnas">
        <section class="tarjeta">
            <h2>Horas pico</h2>
            <p class="ayuda" style="margin-top:-6px">Pedidos pagados según la hora del día.</p>
            @include('admin._barras', [
                'titulo' => 'Pedidos por hora',
                'puntos' => collect($porHora)->map(fn ($h) => ['etiqueta' => substr($h['etiqueta'], 0, 2), 'valor' => $h['pedidos'], 'tip' => $h['etiqueta'].': '.$h['pedidos'].' pedidos · Bs '.number_format($h['ventas'], 2)])->all(),
            ])
            @php($pico = collect($porHora)->sortByDesc('pedidos')->first())
            @if ($pico && $pico['pedidos'])<p class="ayuda" style="margin:10px 0 0">La hora con más pedidos es <strong>{{ $pico['etiqueta'] }}</strong> ({{ $pico['pedidos'] }}).</p>@endif
        </section>
        <section class="tarjeta">
            <h2>Días de la semana</h2>
            <p class="ayuda" style="margin-top:-6px">Ventas según el día.</p>
            @include('admin._barras', [
                'titulo' => 'Ventas por día de la semana',
                'puntos' => collect($porDia)->map(fn ($d) => ['etiqueta' => $d['etiqueta'], 'valor' => $d['ventas'], 'tip' => $d['etiqueta'].': Bs '.number_format($d['ventas'], 2).' · '.$d['pedidos'].' pedidos'])->all(),
            ])
        </section>
    </div>

    <div class="columnas">
        <section class="tarjeta">
            <h2>Ventas por categoría</h2>
            @forelse ($categorias as $c)
                <div class="hbar" title="{{ $c['categoria'] }}: {{ $c['unidades'] }} unidades · Bs {{ number_format($c['ingresos'], 2) }}">
                    <span class="hbar-nombre">{{ $c['categoria'] }}</span>
                    <span class="hbar-pista"><span class="hbar-relleno" style="width: {{ max(1, round($c['porcentaje'], 1)) }}%"></span></span>
                    <span class="hbar-valor">Bs {{ number_format($c['ingresos'], 2) }} <small>{{ number_format($c['porcentaje'], 0) }}%</small></span>
                </div>
            @empty
                <p class="ayuda">Sin ventas en este periodo.</p>
            @endforelse
        </section>

        <section class="tarjeta">
            <h2>Formas de pago</h2>
            @if ($resumen['pedidos'])
                <div class="barra-partida" role="img" aria-label="Formas de pago">
                    @foreach ($metodos as $i => $m)
                        @if ($m['pedidos'])<span class="parte s{{ $i + 1 }}" style="width: {{ $m['porcentaje'] }}%" title="{{ $m['texto'] }}: {{ number_format($m['porcentaje'], 0) }}%"></span>@endif
                    @endforeach
                </div>
            @endif
            @foreach ($metodos as $i => $m)
                <div class="leyenda-fila">
                    <span class="punto s{{ $i + 1 }}"></span>
                    <span class="leyenda-nombre">{{ $m['texto'] }}</span>
                    <span>{{ $m['pedidos'] }} {{ $m['pedidos'] === 1 ? 'pedido' : 'pedidos' }} · <strong>Bs {{ number_format($m['ventas'], 2) }}</strong> · {{ number_format($m['porcentaje'], 0) }}%</span>
                </div>
            @endforeach

            <h2 style="margin-top:22px">Estado actual de los pedidos</h2>
            @foreach (['pendiente_pago', 'pagado', 'en_preparacion', 'listo', 'entregado', 'cancelado'] as $e)
                <div class="resumen-linea"><span><span class="etiqueta estado-{{ $e }}">{{ \App\Models\Pedido::TEXTOS[$e] }}</span></span><strong>{{ $estadosActuales[$e] ?? 0 }}</strong></div>
            @endforeach
        </section>
    </div>

    <section class="tarjeta">
        <div class="est-cab">
            <h2>Productos más vendidos</h2>
            <a href="{{ route('productos.index') }}">Ver stock de todos →</a>
        </div>
        @if ($productos->count())
            @php($maxU = max(1, $productos->max('unidades')))
            <div class="tabla-wrap">
                <table>
                    <thead><tr><th>N.º</th><th>Producto</th><th>Categoría</th><th>Unidades vendidas</th><th class="num">Ingresos</th><th class="num">Stock actual</th></tr></thead>
                    <tbody>
                        @foreach ($productos->take(15) as $i => $p)
                            <tr>
                                <td class="num-fila">{{ $i + 1 }}</td>
                                <td><strong>{{ $p['nombre'] }}</strong></td>
                                <td>{{ $p['categoria'] }}</td>
                                <td><span class="mini-barra"><span style="width: {{ round($p['unidades'] / $maxU * 100) }}%"></span></span> {{ $p['unidades'] }}</td>
                                <td class="num">Bs {{ number_format($p['ingresos'], 2) }}</td>
                                <td class="num">{{ $p['stock'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="ayuda">Sin ventas en este periodo.</p>
        @endif
    </section>

    <div class="columnas">
        <section class="tarjeta">
            <h2>Mejores clientes</h2>
            @forelse ($clientes as $i => $c)
                <div class="resumen-linea"><span><strong>{{ $i + 1 }}.</strong> {{ $c['nombre'] }} <span class="ayuda">· {{ $c['correo'] }} · {{ $c['pedidos'] }} {{ $c['pedidos'] === 1 ? 'pedido' : 'pedidos' }}</span></span><strong>Bs {{ number_format($c['gasto'], 2) }}</strong></div>
            @empty
                <p class="ayuda">Sin compras en este periodo.</p>
            @endforelse

            <h2 style="margin-top:22px">Cupones usados</h2>
            @forelse ($cupones as $c)
                <div class="resumen-linea"><span><span class="codigo">{{ $c['codigo'] }}</span> · {{ $c['usos'] }} {{ $c['usos'] === 1 ? 'uso' : 'usos' }}</span><strong>−Bs {{ number_format($c['descuento'], 2) }}</strong></div>
            @empty
                <p class="ayuda">No se usaron cupones en este periodo.</p>
            @endforelse
        </section>

        <section class="tarjeta">
            <h2>Cancelaciones y reembolsos</h2>
            <div class="resumen-linea"><span>Pedidos cancelados</span><strong>{{ $cancelaciones['cantidad'] }} ({{ number_format($cancelaciones['porcentaje'], 0) }}%)</strong></div>
            <div class="resumen-linea"><span>Cancelados por el cliente</span><strong>{{ $cancelaciones['porCliente'] }}</strong></div>
            <div class="resumen-linea"><span>Cancelados por la cafetería</span><strong>{{ $cancelaciones['porPersonal'] }}</strong></div>
            <div class="resumen-linea"><span>Cancelados solos (no pagaron a tiempo)</span><strong>{{ $cancelaciones['porSistema'] }}</strong></div>
            <div class="resumen-linea"><span>Reembolsos realizados</span><strong>{{ $cancelaciones['reembolsados'] }} · Bs {{ number_format($cancelaciones['montoReembolsado'], 2) }}</strong></div>
            <div class="resumen-linea"><span>Reembolsos pendientes (hoy)</span><strong class="{{ $cancelaciones['reembolsosPendientes'] ? 'texto-alerta' : '' }}">{{ $cancelaciones['reembolsosPendientes'] }} · Bs {{ number_format($cancelaciones['montoPendiente'], 2) }}</strong></div>

            <h2 style="margin-top:22px">Atención y soporte</h2>
            <div class="resumen-linea"><span>Tiempo promedio del pago a la entrega</span><strong>{{ $min($resumen['hastaEntrega']) }}</strong></div>
            <div class="resumen-linea"><span>Consultas de soporte nuevas</span><strong>{{ $soporte['nuevos'] }}</strong></div>
            <div class="resumen-linea"><span>Consultas resueltas</span><strong>{{ $soporte['resueltos'] }}</strong></div>
            <div class="resumen-linea"><span>Consultas por atender (hoy)</span><strong class="{{ $soporte['pendientes'] ? 'texto-alerta' : '' }}">{{ $soporte['pendientes'] }}</strong></div>
        </section>
    </div>

    <div class="columnas">
        <section class="tarjeta">
            <div class="est-cab">
                <h2>Stock más bajo</h2>
                <a href="{{ route('productos.index', ['estado' => 'stock_bajo']) }}">Reponer →</a>
            </div>
            @foreach ($stockBajo as $p)
                <div class="resumen-linea"><span>{{ $p->nombre }} <span class="ayuda">· {{ $p->categoria }}</span></span><strong class="{{ $p->stock <= 0 ? 'texto-alerta' : ($p->stock <= \App\Models\Producto::STOCK_BAJO ? 'texto-ambar' : '') }}">{{ $p->stock }} {{ $p->stock === 1 ? 'unidad' : 'unidades' }}</strong></div>
            @endforeach
        </section>
        <section class="tarjeta">
            <h2>Productos sin ventas en el periodo <span class="etiqueta">{{ $sinVentas->count() }}</span></h2>
            @if ($sinVentas->isEmpty())
                <p class="ayuda">Todos los productos se vendieron al menos una vez.</p>
            @else
                <p class="ayuda">Puede convenir promocionarlos, cambiar su precio o quitarlos del menú.</p>
                <div class="chips-lista">
                    @foreach ($sinVentas->take(30) as $p)<span>{{ $p->nombre }}</span>@endforeach
                    @if ($sinVentas->count() > 30)<span>+{{ $sinVentas->count() - 30 }} más</span>@endif
                </div>
            @endif
        </section>
    </div>
@endsection
