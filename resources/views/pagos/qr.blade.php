@extends('layouts.panel')

@section('titulo', 'Pagar con QR')

@section('contenido')
    <h1>Pagar con código QR</h1>

    <div class="columnas">
        <section class="tarjeta">
            @if ($pago->estado === 'pendiente')
                @if ($modoBanco)
                    {{-- Modo real: el QR de tu banco --}}
                    <p class="ayuda centro">Escanea con la app de tu banco</p>

                    @if ($qrBanco)
                        <div class="qr-caja">
                            <img src="{{ $qrBanco }}" alt="QR de cobro" width="280">
                        </div>
                    @else
                        <div class="alerta">
                            Falta la imagen del QR de cobro. Guárdala como <strong>public/img/qr-cobro.png</strong>.
                        </div>
                    @endif

                    @if (config('cafeqr.banco_titular'))
                        <p class="centro">Pagar a: <strong>{{ config('cafeqr.banco_titular') }}</strong>
                            @if (config('cafeqr.banco_nombre')) ({{ config('cafeqr.banco_nombre') }}) @endif
                        </p>
                    @endif

                    <p class="ayuda centro">Escribe exactamente este monto:</p>
                    <p class="monto-pago">Bs {{ number_format($pago->monto, 2) }}</p>
                    <p class="ayuda centro">
                        Si tu app permite escribir un detalle, pon:
                        <span class="codigo">Pedido #{{ $pedido->id }}</span>
                    </p>
                    <p class="centro">⏱ Tienes <strong id="cuenta">--:--</strong> para pagar</p>

                    <form method="POST" action="{{ route('pago.yapague', $pago) }}">
                        @csrf
                        <label for="comprobante">Número de comprobante (opcional)</label>
                        <input type="text" id="comprobante" name="comprobante" maxlength="60" placeholder="El que aparece en tu app al pagar">
                        <button type="submit" class="btn">Ya realicé el pago</button>
                    </form>
                @else
                    {{-- Modo demostración: QR de prueba --}}
                    <p class="ayuda centro">Escanea este código con la app de tu banco</p>

                    <div class="qr-caja">
                        <img src="{{ $qr }}" alt="Código QR para pagar el pedido #{{ $pedido->id }}" width="260" height="260">
                    </div>

                    <p class="monto-pago">Bs {{ number_format($pago->monto, 2) }}</p>
                    <p class="ayuda centro">
                        Pedido #{{ $pedido->id }} · Referencia
                        <span class="codigo">{{ strtoupper(substr($pago->referencia, 0, 8)) }}</span>
                    </p>
                    <p class="centro">⏱ El código vence en <strong id="cuenta">--:--</strong></p>
                    <p class="ayuda centro">Esperando la confirmación del pago…</p>
                @endif
            @elseif ($pago->estado === 'por_verificar')
                <div class="exito-caja">
                    <div class="icono-correo">⏳</div>
                    <h2>Estamos verificando tu pago</h2>
                    <p>Avisamos a la cafetería. Cuando confirme que el dinero llegó, te enviaremos un código a tu correo.</p>
                    @if ($pago->comprobante)
                        <p class="ayuda">Comprobante: {{ $pago->comprobante }}</p>
                    @endif
                    <p class="ayuda">Esta pantalla se actualiza sola.</p>
                </div>
            @else
                <div class="alerta">
                    El pago no se completó. {{ $pago->detalle }}
                </div>
                <p class="ayuda">Si ya pagaste de verdad, no repitas el pago: habla con la cafetería. Si no, puedes intentarlo otra vez.</p>

                <form method="POST" action="{{ route('pago.reintentar', $pago) }}">
                    @csrf
                    <button type="submit" class="btn">Reintentar pago (nuevo QR)</button>
                </form>
            @endif

            @if ($pago->estado !== 'por_verificar')
                <div class="acciones">
                    <a class="btn gris" href="{{ route('pedido.confirmar') }}">Volver al pedido</a>
                </div>
            @endif
        </section>

        <section class="tarjeta">
            <h2>Resumen</h2>

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
            <p class="ayuda">Factura a nombre de {{ $pedido->razon_social }} · NIT/CI {{ $pedido->nit_ci }}</p>

            @if ($pago->metodo === 'demo' && $pago->estado === 'pendiente')
                <div class="demo-box">
                    <strong>Modo demostración</strong>
                    <p class="ayuda">Un pago real lo haría tu banco. Para probar, usa estos botones:</p>

                    <form method="POST" action="{{ route('pago.simular', $pago) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="resultado" value="exito">
                        <button type="submit" class="btn chico">✅ Simular pago exitoso</button>
                    </form>

                    <form method="POST" action="{{ route('pago.simular', $pago) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="resultado" value="fallo">
                        <button type="submit" class="btn chico rojo">❌ Simular pago fallido</button>
                    </form>

                    <p class="ayuda" style="margin-top:10px;">
                        O abre la
                        <a href="{{ route('pago.escanear', $pago->referencia) }}" target="_blank">app del banco de prueba</a>,
                        que es lo que se vería al escanear el QR con el celular.
                    </p>
                </div>
            @endif

            <h3>Intentos de pago</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Hora</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($intentos as $i => $intento)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $intento->created_at->format('H:i:s') }}</td>
                            <td>
                                <span class="etiqueta estado-{{ $intento->estado === 'fallido' ? 'cancelado' : ($intento->estado === 'pagado' ? 'entregado' : 'pendiente') }}">
                                    {{ str_replace('_', ' ', ucfirst($intento->estado)) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
@endsection

@if (in_array($pago->estado, ['pendiente', 'por_verificar'], true))
    @push('scripts')
    <script>
        (function () {
            var estadoActual = "{{ $pago->estado }}";

            @if ($pago->estado === 'pendiente' && $pago->expira_en)
            var vence = new Date("{{ $pago->expira_en->toIso8601String() }}").getTime();
            var cuenta = document.getElementById('cuenta');

            function tick() {
                var s = Math.max(0, Math.floor((vence - Date.now()) / 1000));
                if (cuenta) {
                    cuenta.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
                }
                if (s === 0) { window.location.reload(); }
            }
            tick();
            setInterval(tick, 1000);
            @endif

            // La pantalla se actualiza sola cuando el estado del pago cambia
            setInterval(function () {
                fetch("{{ route('pago.estado', $pago) }}", { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.estado !== estadoActual) { window.location.reload(); }
                    })
                    .catch(function () {});
            }, 3000);
        })();
    </script>
    @endpush
@endif