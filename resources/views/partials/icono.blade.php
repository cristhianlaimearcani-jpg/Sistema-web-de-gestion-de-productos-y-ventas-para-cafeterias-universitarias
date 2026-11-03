{{-- Iconos de línea (en vez de emojis). Uso: @include('partials.icono', ['n' => 'carrito']) --}}
@php
    $trazos = [
        'carrito'   => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3.5h3l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1L21 7.5H6.4"/>',
        'usuario'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'recibo'    => '<path d="M6 2.5h12v19l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'celular'   => '<rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M10.5 18.5h3"/>',
        'efectivo'  => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5v5M18 9.5v5"/>',
        'tienda'    => '<path d="M3.5 9.5 5 4h14l1.5 5.5"/><path d="M3.5 9.5a2.8 2.8 0 0 0 5.6 0 2.9 2.9 0 0 0 5.8 0 2.8 2.8 0 0 0 5.6 0"/><path d="M5 12v8.5h14V12"/><path d="M10 20.5v-5h4v5"/>',
        'caminar'   => '<circle cx="13" cy="4" r="1.8"/><path d="m9 21 2.5-6.5 3 2.5V21"/><path d="m7 11 3-4 4 1 2.5 3.5H19"/><path d="m11.5 14.5 1-6.5"/>',
        'correo'    => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="m3 6.5 9 6.5 9-6.5"/>',
        'campana'   => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
        'buscar'    => '<circle cx="11" cy="11" r="6.5"/><path d="m20.5 20.5-4.8-4.8"/>',
        'reloj'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'reloj-arena' => '<path d="M6.5 2.5h11M6.5 21.5h11"/><path d="M7.5 2.5c0 5 9 6 9 9.5s-9 4.5-9 9.5"/><path d="M16.5 2.5c0 5-9 6-9 9.5s9 4.5 9 9.5"/>',
        'cupon'     => '<path d="M3 8.5V6a1.5 1.5 0 0 1 1.5-1.5h15A1.5 1.5 0 0 1 21 6v2.5a3.5 3.5 0 0 0 0 7V18a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18v-2.5a3.5 3.5 0 0 0 0-7z"/><path d="M14 5v2M14 11v2M14 17v2"/>',
        'imprimir'  => '<path d="M7 8V3h10v5"/><rect x="3" y="8" width="18" height="9" rx="2"/><path d="M7 14h10v7H7z"/>',
        'camara'    => '<path d="M3 8a2 2 0 0 1 2-2h2.5L9 4h6l1.5 2H19a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="12" cy="13" r="3.8"/>',
        'taza'      => '<path d="M4 9h13v5a6 6 0 0 1-6 6h-1a6 6 0 0 1-6-6z"/><path d="M17 11h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8.5 2.5c-.6 1 .6 2 0 3.3M12.5 2.5c-.6 1 .6 2 0 3.3"/>',
        'plato'     => '<circle cx="12" cy="13" r="7.5"/><circle cx="12" cy="13" r="4"/><path d="M2.5 3v5a2 2 0 0 0 2 2M4.5 3v18M21.5 3v18M21.5 3c-2 1-3 3.5-3 6.5 0 1.4 1 2.5 3 2.5"/>',
        'check'     => '<circle cx="12" cy="12" r="9"/><path d="m8 12.3 2.7 2.7L16.2 9.5"/>',
        'fiesta'    => '<path d="m3 21 4.5-12.5 8 8z"/><path d="M14 4.5c.5 1.5-.5 2.5-1.5 3M19.5 10c-1.5-.5-2.5.5-3 1.5M16.5 2.5l.3 1M21.5 7.5l-1 .3M19 5l-1.2 1.2"/>',
        'alerta'    => '<path d="M12 3.5 2.5 20h19z"/><path d="M12 10v4.5M12 17.2v.3"/>',
        'devolver'  => '<path d="M4 10h11a5 5 0 0 1 0 10h-3"/><path d="m8 6-4 4 4 4"/>',
        'matraz'    => '<path d="M9 2.5h6M10 2.5v6L4.5 19a1.5 1.5 0 0 0 1.3 2.5h12.4a1.5 1.5 0 0 0 1.3-2.5L14 8.5v-6"/><path d="M7 15h10"/>',
    ];
@endphp
<svg class="icono-svg {{ $clase ?? '' }}" viewBox="0 0 24 24" width="{{ $tam ?? 20 }}" height="{{ $tam ?? 20 }}" fill="none" stroke="currentColor" stroke-width="{{ $grosor ?? 1.9 }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $trazos[$n] ?? '' !!}</svg>
