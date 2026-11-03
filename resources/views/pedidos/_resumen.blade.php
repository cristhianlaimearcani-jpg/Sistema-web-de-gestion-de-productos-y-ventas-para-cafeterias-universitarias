{{-- Resumen claro de la compra: número, productos, total y estado --}}
<div class="resumen-compra">
    <div class="resumen-compra-cab">
        <strong>Pedido #{{ $pedido->id }}</strong>
        <span class="etiqueta estado-{{ $pedido->estado }}">{{ $pedido->estado_texto }}</span>
    </div>
    <p class="ayuda" style="margin:0 0 6px">
        Registrado el {{ $pedido->updated_at->timezone(config('app.timezone'))->format('d/m/Y \a \l\a\s H:i') }}
        @if ($pedido->metodo_pago || isset($pago)) · {{ ($pedido->metodo_pago ?? $pago->metodo) === 'efectivo' ? 'Pago en efectivo' : 'Pago con QR' }} @endif
    </p>
    @foreach ($pedido->detalles as $detalle)
        <div class="fila"><span>{{ $detalle->cantidad }} × {{ $detalle->nombre }}</span><span>Bs {{ number_format($detalle->subtotal, 2) }}</span></div>
    @endforeach
    @if ($pedido->descuento > 0)
        <div class="fila"><span>Cupón {{ $pedido->cupon }}</span><span>−Bs {{ number_format($pedido->descuento, 2) }}</span></div>
    @endif
    <div class="fila total"><span>Total</span><span>Bs {{ number_format($pedido->total, 2) }}</span></div>
</div>
