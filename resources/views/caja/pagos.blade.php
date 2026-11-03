@extends('layouts.app')

@section('titulo', 'Caja')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Caja · confirmar pagos</h1>
            <p class="subtitulo" style="margin:0">Aquí confirmas los pagos en efectivo y los pagos con QR que el cliente avisó. Al confirmar, el pedido pasa a Entregas.</p>
        </div>
        <a class="btn gris" href="{{ route('entregas.index') }}">Ir a Entregas →</a>
    </div>

    @if ($modoPrueba)
        <div class="aviso">@include('partials.icono', ['n' => 'alerta', 'tam' => 17]) El correo está en <strong>modo prueba</strong> (MAIL_MAILER=log): los clientes NO reciben los mensajes. Configura Gmail en el <code>.env</code> y prueba con <code>php artisan correo:probar tucorreo</code>.</div>
    @endif

    <section class="tarjeta">
        <h2>@include('partials.icono', ['n' => 'efectivo', 'tam' => 22]) Cobros en efectivo <span class="etiqueta estado-pendiente_pago">{{ $efectivo->count() }}</span></h2>
        @forelse ($efectivo as $pago)
            @include('caja._pago-fila', ['pago' => $pago, 'textoBoton' => 'Cobrado en efectivo'])
        @empty
            <p class="ayuda">No hay cobros en efectivo pendientes.</p>
        @endforelse
    </section>

    <section class="tarjeta">
        <h2>@include('partials.icono', ['n' => 'celular', 'tam' => 22]) Pagos con QR por verificar <span class="etiqueta estado-pendiente_pago">{{ $porVerificar->count() }}</span></h2>
        <p class="ayuda">El cliente avisó que ya pagó. Revisa en tu app del banco que el dinero llegó, escribe el n.º de transacción del comprobante y confirma: el cliente recibe al instante su código de recojo.</p>
        @forelse ($porVerificar as $pago)
            @include('caja._pago-fila', ['pago' => $pago, 'textoBoton' => 'Recibí el dinero'])
        @empty
            <p class="ayuda">No hay pagos por verificar.</p>
        @endforelse
    </section>

    <section class="tarjeta">
        <h2>@include('partials.icono', ['n' => 'check', 'tam' => 22]) Pagos confirmados hoy <span class="etiqueta estado-pagado">{{ $confirmadosHoy->count() }}</span></h2>
        <p class="ayuda">Cada pago queda unido a su pedido con su n.º de transacción. Una misma transacción no se puede acreditar dos veces.</p>
        @if ($confirmadosHoy->isEmpty())
            <p class="ayuda">Todavía no se confirmó ningún pago hoy.</p>
        @else
            <div class="tabla-wrap">
                <table>
                    <thead><tr><th>Hora</th><th>Pedido</th><th>Cliente</th><th>Forma</th><th>Transacción</th><th>Confirmado por</th><th class="num">Monto</th></tr></thead>
                    <tbody>
                        @foreach ($confirmadosHoy as $pago)
                            <tr>
                                <td>{{ $pago->confirmado_en->timezone(config('app.timezone'))->format('H:i') }}</td>
                                <td>#{{ $pago->pedido_id }}</td>
                                <td>{{ $pago->pedido->user->name }}</td>
                                <td>{{ $pago->esEfectivo() ? 'Efectivo' : 'QR' }}</td>
                                <td><span class="codigo">{{ $pago->transaccion }}</span></td>
                                <td>{{ ['celular' => 'Cliente (celular)', 'simulado' => 'Simulación', 'caja' => 'Caja'][$pago->confirmado_por] ?? '—' }}</td>
                                <td class="num">Bs {{ number_format($pago->monto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
