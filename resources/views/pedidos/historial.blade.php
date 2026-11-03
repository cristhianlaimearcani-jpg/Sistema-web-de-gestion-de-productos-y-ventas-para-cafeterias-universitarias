@extends('layouts.app')

@section('titulo', 'Mis pedidos')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Mis pedidos</h1>
            <p class="subtitulo" style="margin:0">Todo lo que pediste, con su estado y su factura.</p>
        </div>
        <a class="btn" href="{{ route('dashboard') }}">+ Nuevo pedido</a>
    </div>

    <nav class="filtro-estados" aria-label="Filtrar mis pedidos">
        <a href="{{ route('pedidos.historial') }}" class="{{ $grupo ? '' : 'activo' }}">Todos <small>{{ $cuentas['todos'] }}</small></a>
        @foreach (\App\Models\Pedido::GRUPOS as $clave => $g)
            <a href="{{ route('pedidos.historial', ['ver' => $clave]) }}" class="g-{{ $clave }} {{ $grupo === $clave ? 'activo' : '' }}">{{ $g['texto'] }} <small>{{ $cuentas[$clave] }}</small></a>
        @endforeach
    </nav>

    @forelse ($pedidos as $pedido)
        <article class="pedido-card {{ $pedido->estado === 'listo' ? 'listo' : '' }}">
            <div class="pedido-cab">
                <strong>Pedido #{{ $pedido->id }}</strong>
                <span class="etiqueta estado-{{ $pedido->estado }}">{{ $pedido->estado_texto }}</span>
            </div>
            <p class="ayuda" style="margin:4px 0 0">
                {{ $pedido->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                @if ($pedido->metodo_pago) · {{ $pedido->metodo_pago === 'qr' ? 'Pago con QR' : 'Efectivo' }} @endif
                @if ($pedido->codigo) · Código de recojo: <span class="codigo">{{ $pedido->codigo }}</span> @endif
            </p>
            <ul class="pedido-items">
                @foreach ($pedido->detalles as $detalle)
                    <li>{{ $detalle->cantidad }} × {{ $detalle->nombre }}</li>
                @endforeach
            </ul>
            @if ($pedido->estado === 'en_preparacion' && $pedido->minutosRestantes() !== null)
                <p class="ayuda">@include('partials.icono', ['n' => 'reloj', 'tam' => 15]) Listo en aprox. {{ $pedido->minutosRestantes() }} min</p>
            @endif
            @if ($pedido->estado === 'cancelado')
                <p class="ayuda">Cancelado {{ ['cliente' => 'por ti', 'sistema' => 'automáticamente'][$pedido->cancelado_por] ?? 'por la cafetería' }}@if ($pedido->motivo_cancelacion): “{{ $pedido->motivo_cancelacion }}”@endif.</p>
                @if ($pedido->reembolso_pendiente)
                    <p class="reembolso pendiente">@include('partials.icono', ['n' => 'devolver', 'tam' => 16]) <strong>Reembolso pendiente</strong> de Bs {{ number_format($pedido->total, 2) }}: pasa por la cafetería para que te devuelvan tu dinero.</p>
                @elseif ($pedido->reembolsado_en)
                    <p class="reembolso hecho">@include('partials.icono', ['n' => 'check', 'tam' => 16]) <strong>Reembolso realizado</strong> el {{ $pedido->reembolsado_en->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.</p>
                @endif
            @elseif ($pedido->estado === 'pagado')
                <p class="ayuda">Esperando que la cafetería empiece a prepararlo. Todavía puedes cancelarlo.</p>
            @endif

            <div class="pedido-acciones">
                <strong style="color:var(--guinda)">Bs {{ number_format($pedido->total, 2) }}</strong>
                @if ($pedido->estado === 'pendiente_pago')
                    <a class="btn chico" href="{{ route('pedido.continuar', ['pedido' => $pedido->id]) }}">Continuar con el pago</a>
                @elseif (in_array($pedido->estado, ['pagado', 'en_preparacion', 'listo', 'entregado']))
                    <a class="btn chico" href="{{ route('pedido.seguimiento', $pedido) }}">Seguir mi pedido</a>
                @endif
                @if ($pedido->factura)
                    <a class="btn chico contorno" href="{{ route('factura.show', $pedido) }}">Ver factura</a>
                @endif
                <a class="enlace-ayuda" href="{{ route('soporte.index', ['pedido' => $pedido->id]) }}">¿Problema con este pedido?</a>
            </div>
        </article>
    @empty
        <div class="tarjeta vacio">
            <span class="grande">@include('partials.icono', ['n' => 'recibo', 'tam' => 44, 'grosor' => 1.5])</span>
            @if ($grupo) No tienes pedidos en «{{ \App\Models\Pedido::GRUPOS[$grupo]['texto'] }}». @else Todavía no hiciste ningún pedido. @endif<br>
            <a class="btn" href="{{ route('dashboard') }}">Ver el menú</a>
        </div>
    @endforelse

    {{ $pedidos->links() }}
@endsection
