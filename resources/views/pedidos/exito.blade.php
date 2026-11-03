@extends('layouts.app')

@section('titulo', '¡Gracias por tu compra!')
@section('ancho', 'angosto')

@section('contenido')
    <section class="tarjeta gracias">
        <div class="marca-ok">✓</div>
        <h1>¡Gracias por tu compra!</h1>
        <p class="subtitulo" style="margin-bottom:8px">Tu compra quedó registrada. Muestra este código en la cafetería para recoger tu <strong>pedido #{{ $pedido->id }}</strong>.</p>

        <div class="codigo-grande">{{ $pedido->codigo }}</div>

        <div class="qr-caja chico"><img src="{{ $qrRecojo }}" alt="QR de recojo del pedido {{ $pedido->id }}"></div>

        @include('pedidos._resumen')

        <div class="aviso" style="text-align:left">
            @include('partials.icono', ['n' => 'correo', 'tam' => 17]) Te avisaremos por correo a <strong>{{ $pedido->user->email }}</strong> cuando tu pedido esté <strong>en preparación</strong> (con el tiempo estimado) y cuando esté <strong>listo</strong>.
        </div>

        <div class="acciones" style="justify-content:center">
            <a class="btn" href="{{ route('pedido.seguimiento', $pedido) }}">Seguir mi pedido</a>
            <a class="btn contorno" href="{{ route('pedidos.historial') }}">Mis pedidos</a>
        </div>
    </section>

    @isset($factura)
        <section class="tarjeta factura">
            @include('facturas._documento')
            <div class="acciones no-imprimir">
                <a class="btn contorno" href="{{ route('factura.show', $pedido) }}">Abrir factura / imprimir</a>
            </div>
        </section>
    @endisset
@endsection
