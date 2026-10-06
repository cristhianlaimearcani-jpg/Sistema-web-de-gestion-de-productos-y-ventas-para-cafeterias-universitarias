@extends('layouts.panel')

@section('titulo', '¡Gracias por tu compra!')

@section('contenido')
    @if (session('gracias'))
        <div class="toast" id="toast">
            🎉 ¡Gracias por tu compra, {{ $pedido->user->name }}!<br>
            <small>Tu pedido #{{ $pedido->id }} está confirmado.</small>
        </div>
    @endif

    <section class="tarjeta gracias no-imprimir">
        <div class="check">✓</div>
        <h1>¡Gracias por tu compra!</h1>
        <p>Tu pago fue confirmado. Presenta este código en la cafetería para recoger tu pedido:</p>

        <div class="codigo-grande">{{ $pedido->codigo_recojo }}</div>

        <div class="qr-caja chico">
            <img src="{{ $qrRecojo }}" alt="QR del código de recojo" width="170" height="170">
        </div>

        <p class="ayuda">
            Código único, válido solo para este pedido. Enviamos un comprobante a tu correo;
            si no llega, revisa la carpeta de spam. Tu factura está más abajo y queda guardada en "Mis pedidos".
        </p>

        <div class="acciones" style="justify-content:center;">
            <button type="button" class="btn" onclick="window.print()">🖨️ Imprimir factura</button>
            <a class="btn gris" href="{{ route('pedidos.historial') }}">Mis pedidos</a>
            <a class="btn gris" href="{{ route('dashboard') }}">Seguir pidiendo</a>
        </div>
    </section>

    @include('facturas._documento')
@endsection

@push('scripts')
<script>
    // La notificación se esconde sola después de unos segundos
    setTimeout(function () {
        var t = document.getElementById('toast');
        if (t) { t.classList.add('oculto'); }
    }, 7000);
</script>
@endpush