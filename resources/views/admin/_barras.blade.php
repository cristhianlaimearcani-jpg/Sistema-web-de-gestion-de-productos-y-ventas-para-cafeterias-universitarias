{{-- Gráfico de barras verticales. $puntos: [['etiqueta'=>..., 'valor'=>..., 'tip'=>...], ...] --}}
@php
    $max = max(1, collect($puntos)->max('valor'));
    $n = count($puntos);
    $cada = $n > 16 ? (int) ceil($n / 10) : 1;
@endphp
<div class="grafico-barras" role="img" aria-label="{{ $titulo ?? 'Gráfico de barras' }}">
    <div class="gb-area">
        @foreach ($puntos as $i => $p)
            <div class="gb-col" tabindex="0" data-tip="{{ $p['tip'] }}">
                <div class="gb-barra {{ $p['valor'] <= 0 ? 'vacia' : '' }}" style="height: {{ $p['valor'] > 0 ? max(2, round($p['valor'] / $max * 100, 1)) : 0 }}%"></div>
            </div>
        @endforeach
    </div>
    <div class="gb-ejes">
        @foreach ($puntos as $i => $p)
            <span class="{{ ($i % ($cada * 2) !== 0) ? 'solo-ancho' : '' }}">{{ ($i % $cada === 0) ? $p['etiqueta'] : '' }}</span>
        @endforeach
    </div>
</div>
