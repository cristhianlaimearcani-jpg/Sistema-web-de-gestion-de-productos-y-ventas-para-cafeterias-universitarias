@extends('layouts.app')

@section('titulo', 'Confirmar pago')

@section('contenido')
    <h1>Confirmar pago</h1>
    <p class="ayuda">Simulación de la app del banco (modo demostración).</p>

    @php $resultado = session('resultado'); @endphp

    @if ($resultado === 'recibido')
        <div class="exito">✅ ¡Pago realizado! Vuelve a CafeQR: te enviamos un código a tu correo para confirmar la compra.</div>
    @elseif ($resultado === 'rechazado')
        <div class="alerta">El pago fue rechazado. No se te cobró nada.</div>
    @elseif ($resultado === 'vencido')
        <div class="alerta">Este código QR venció. Genera uno nuevo en CafeQR.</div>
    @elseif ($resultado === 'ya_recibido')
        <div class="exito">Este pago ya estaba realizado. No se cobra dos veces.</div>
    @elseif (in_array($resultado, ['fallido', 'pedido_cerrado'], true))
        <div class="alerta">Este código ya no es válido. Genera uno nuevo en CafeQR.</div>
    @endif

    <div class="resumen-banco">
        <p class="dato"><strong>Comercio:</strong> {{ config('cafeqr.emisor_nombre') }}</p>
        <p class="dato"><strong>Pedido:</strong> #{{ $pago->pedido_id }}</p>
        <p class="monto-pago">Bs {{ number_format($pago->monto, 2) }}</p>
    </div>

    @if ($pago->estado === 'pendiente')
        <form method="POST" action="{{ route('pago.ejecutar', $pago->referencia) }}">
            @csrf
            <input type="hidden" name="accion" value="pagar">
            <button type="submit">Pagar Bs {{ number_format($pago->monto, 2) }}</button>
        </form>

        <form method="POST" action="{{ route('pago.ejecutar', $pago->referencia) }}">
            @csrf
            <input type="hidden" name="accion" value="rechazar">
            <button type="submit" style="background:#6b5b60;">Rechazar</button>
        </form>
    @elseif (in_array($pago->estado, ['recibido', 'pagado'], true) && ! $resultado)
        <div class="exito">Este pago ya fue realizado.</div>
    @elseif ($pago->estado === 'fallido' && ! $resultado)
        <div class="alerta">Este código ya no es válido. {{ $pago->detalle }}</div>
    @endif
@endsection