@extends('layouts.panel')

@section('titulo', 'Seguimiento del pedido')

@section('contenido')
    @php
        $pasos = [
            'pagado'         => 'Pago confirmado',
            'en_preparacion' => 'En preparación',
            'listo'          => 'Listo para recoger',
            'entregado'      => 'Entregado',
        ];
        $posicion = array_search($pedido->estado, array_keys($pasos), true);
    @endphp

    <section class="tarjeta">
        <div class="pedido-cab">
            <h1 style="margin:0;">Pedido #{{ $pedido->id }}</h1>
            <span class="etiqueta estado-{{ $pedido->estado }}">{{ $pedido->estado_texto }}</span>
        </div>

        @if ($posicion === false)
            <div class="alerta" style="margin-top:14px;">Este pedido todavía no está pagado.</div>
        @else
            <ol class="pasos">
                @foreach ($pasos as $clave => $texto)
                    <li class="{{ ($loop->index < $posicion || $pedido->estado === 'entregado') ? 'hecho' : ($loop->index === $posicion ? 'actual' : '') }}">
                        <span class="num">{{ $loop->iteration }}</span>{{ $texto }}
                    </li>
                @endforeach
            </ol>

            @if ($pedido->estado === 'pagado')
                <p class="centro">Recibimos tu pedido. La cafetería lo empezará a preparar en breve.</p>
            @elseif ($pedido->estado === 'en_preparacion')
                <p class="centro">👨‍🍳 Estamos preparando tu pedido.</p>
            @elseif ($pedido->estado === 'listo')
                <div class="exito centro">🔔 ¡Tu pedido está listo! Preséntate en la cafetería con tu código.</div>
            @elseif ($pedido->estado === 'entregado')
                <p class="centro">✅ Pedido entregado. ¡Gracias por tu compra!</p>
            @endif

            @if ($pedido->codigo_recojo && $pedido->estado !== 'entregado')
                <p class="centro ayuda">Tu código de recojo:</p>
                <p class="centro"><span class="codigo-grande">{{ $pedido->codigo_recojo }}</span></p>
            @endif

            <h3>Tu pedido</h3>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pedido->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto->nombre }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>Bs {{ number_format($detalle->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="total">Total: Bs {{ number_format($pedido->total, 2) }}</p>

            <div class="acciones">
                <a class="btn gris" href="{{ route('pedidos.historial') }}">Mis pedidos</a>
                @if ($pedido->factura)
                    <a class="btn gris" href="{{ route('factura.show', $pedido) }}">Ver factura</a>
                @endif
            </div>
        @endif
    </section>
@endsection

@if ($posicion !== false && $pedido->estado !== 'entregado')
    @push('scripts')
    <script>
        (function () {
            var estadoActual = "{{ $pedido->estado }}";

            // Cada 5 segundos revisa si la cafetería cambió el estado del pedido
            setInterval(function () {
                fetch("{{ route('pedido.estado', $pedido) }}", { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.estado === estadoActual) { return; }

                        var aviso = document.createElement('div');
                        aviso.className = 'toast';
                        aviso.textContent = d.estado === 'listo'
                            ? '🔔 ¡Tu pedido está listo para recoger!'
                            : 'Tu pedido cambió a: ' + d.texto;
                        document.body.appendChild(aviso);

                        setTimeout(function () { window.location.reload(); }, 1800);
                    })
                    .catch(function () {});
            }, 5000);
        })();
    </script>
    @endpush
@endif