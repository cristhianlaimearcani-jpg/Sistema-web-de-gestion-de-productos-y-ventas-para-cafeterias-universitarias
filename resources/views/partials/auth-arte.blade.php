{{-- Motion graphic del espacio libre del login/registro (solo pantallas grandes, solo decorativo).
     Muestra el flujo de CafeQR: productos del menú, pago con QR y código de recojo. --}}
@php
    $chips = collect();
    try {
        $chips = \App\Models\Producto::destacados(3)->take(3)->map(fn ($p) => [
            'nombre' => $p->nombre,
            'precio' => (float) $p->precio,
            'img' => $p->imagen_url ?: \App\Models\Producto::imagenDeCategoria($p->categoria),
        ]);
    } catch (\Throwable $e) {
    }
    if ($chips->count() < 3) {
        $chips = collect([
            ['nombre' => 'Capuchino', 'precio' => 9.0, 'categoria' => 'Bebidas calientes'],
            ['nombre' => 'Salteña de carne', 'precio' => 8.0, 'categoria' => 'Snacks'],
            ['nombre' => 'Jugo de naranja', 'precio' => 10.0, 'categoria' => 'Bebidas frías'],
        ])->map(fn ($c) => $c + ['img' => \App\Models\Producto::imagenDeCategoria($c['categoria'])]);
    }
    $qr = null;
    try {
        $qr = \App\Support\Qr::dataUri(url('/'));
    } catch (\Throwable $e) {
    }
@endphp
<div class="auth-arte" aria-hidden="true">
    <div class="arte-halo"></div>
    <div class="arte-celular">
        <div class="arte-pantalla">
            <div class="arte-top">@include('partials.logo-cafeqr', ['tam' => 22]) <span>CafeQR</span></div>
            <div class="arte-qr">
                @if ($qr)
                    <img src="{{ $qr }}" alt="">
                @else
                    <div class="arte-qr-vacio"></div>
                @endif
                <span class="arte-scan"></span>
                <span class="arte-ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                </span>
            </div>
            <div class="arte-monto">Bs {{ number_format($chips->sum('precio'), 2) }}</div>
            <div class="arte-boton">Pagar con QR</div>
        </div>
    </div>
    @foreach ($chips as $i => $c)
        <div class="arte-chip c{{ $i }}">
            @if ($c['img'])<img src="{{ $c['img'] }}" alt="">@endif
            <span><strong>{{ $c['nombre'] }}</strong><small>Bs {{ number_format($c['precio'], 2) }}</small></span>
        </div>
    @endforeach
    <div class="arte-ticket"><small>Código de recojo</small><strong>A7K3</strong></div>
</div>
