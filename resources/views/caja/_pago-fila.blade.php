<div class="pedido-card">
    <div class="pedido-cab">
        <strong>Pedido #{{ $pago->pedido->id }} · {{ $pago->pedido->user->name }}</strong>
        <strong style="color:var(--guinda)">Bs {{ number_format($pago->monto, 2) }}</strong>
    </div>
    <ul class="pedido-items">
        @foreach ($pago->pedido->detalles as $detalle)
            <li>{{ $detalle->cantidad }} × {{ $detalle->nombre }}</li>
        @endforeach
    </ul>
    <p class="ayuda" style="margin:0">{{ $pago->pedido->user->email }} · {{ $pago->updated_at->timezone(config('app.timezone'))->format('d/m H:i') }}</p>
    <div class="pedido-acciones">
        <form method="POST" action="{{ route('caja.recibido', $pago) }}" class="form-cobro">
            @csrf
            @unless ($pago->esEfectivo())
                <label class="oculto" for="tx-{{ $pago->id }}">N.º de transacción</label>
                <input type="text" id="tx-{{ $pago->id }}" name="transaccion" maxlength="40" required placeholder="N.º de transacción"
                       value="{{ old('transaccion') }}" class="input-transaccion" autocomplete="off">
            @endunless
            <button type="submit" class="btn chico verde">{{ $textoBoton }}</button>
        </form>
        <form method="POST" action="{{ route('caja.rechazar', $pago) }}" onsubmit="return confirm('¿Rechazar este pago?');">
            @csrf
            <button type="submit" class="btn chico rojo">Rechazar</button>
        </form>
    </div>
</div>
