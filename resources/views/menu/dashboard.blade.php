@extends('layouts.app')

@section('titulo', 'Menú')

@section('contenido')
    @php
        $u = auth()->user();
        $primerNombre = \Illuminate\Support\Str::before($u->name, ' ');
    @endphp

    <section class="menu-hero">
        <div>
            @include('partials.logo-univalle')
            <h1>¡Hola, {{ $primerNombre }}!</h1>
            <p>¿Qué se te antoja hoy? Elige, paga con QR o en efectivo y recógelo sin hacer fila.</p>

            <form class="buscador" method="GET" action="{{ route('dashboard') }}" role="search">
                <input type="search" name="q" value="{{ $buscar }}" placeholder="Buscar café, sándwich, postre…" aria-label="Buscar producto" maxlength="60">
                <button type="submit" class="btn dorado">Buscar</button>
            </form>
        </div>
    </section>

    @if (! $u->esCliente())
        <div class="aviso">Estás viendo el menú como <strong>{{ $u->rol_texto }}</strong>. Para agregar, editar o agotar productos ve a <a href="{{ route('productos.index') }}">Productos</a>.</div>
    @endif

    @if ($buscar !== '')
        <p class="ayuda">Resultados para “{{ $buscar }}” · <a href="{{ route('dashboard') }}">ver todo el menú</a></p>
    @endif

    @if (count($categorias) > 1)
        <nav class="tabs-cat" aria-label="Categorías" data-tabs-cat>
            @foreach ($categorias as $categoria => $items)
                @php($slug = \Illuminate\Support\Str::slug($categoria))
                <a href="#cat-{{ $slug }}" class="cat-{{ $slug }}" data-cat="cat-{{ $slug }}">
                    @if ($img = \App\Models\Producto::imagenDeCategoria($categoria))
                        <img src="{{ $img }}" alt="">
                    @endif
                    {{ $categoria }} <small>{{ $items->count() }}</small>
                </a>
            @endforeach
        </nav>
    @endif

    @forelse ($categorias as $categoria => $items)
        @php($slug = \Illuminate\Support\Str::slug($categoria))
        <section class="seccion cat-{{ $slug }}" id="cat-{{ $slug }}">
            <h2 class="seccion-titulo">
                @if ($img = \App\Models\Producto::imagenDeCategoria($categoria))
                    <span class="ico"><img src="{{ $img }}" alt=""></span>
                @endif
                {{ $categoria }}
                <span class="cuenta">{{ $items->count() }} {{ $items->count() === 1 ? 'producto' : 'productos' }}</span>
            </h2>

            <div class="grilla">
                @foreach ($items as $producto)
                    <article class="producto {{ $producto->categoria_clase }} {{ $producto->agotado ? 'sin-stock' : '' }}">
                        <div class="producto-img">
                            @if ($producto->imagen_url)
                                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" loading="lazy">
                            @else
                                {{ $producto->icono }}
                            @endif
                            @if ($producto->agotado)
                                <span class="etq-agotado">Agotado</span>
                            @elseif ($producto->stock_bajo)
                                <span class="quedan">{{ $producto->stock === 1 ? '¡Queda 1!' : 'Quedan '.$producto->stock }}</span>
                            @endif
                        </div>
                        <div class="producto-cuerpo">
                            <h3>{{ $producto->nombre }}</h3>
                            <p class="desc">{{ $producto->descripcion }}</p>
                            <div class="producto-pie">
                                <p class="precio">Bs {{ number_format($producto->precio, 2) }}</p>
                                @if (! $producto->agotado && ! $u->esCliente())
                                    <span class="etiqueta disponible">Stock: {{ $producto->stock }}</span>
                                @endif
                            </div>

                            @if (! $producto->agotado && $u->esCliente())
                                <form class="agregar" method="POST" action="{{ route('pedido.agregar', $producto) }}">
                                    @csrf
                                    <div class="stepper">
                                        <button type="button" data-paso="-1" aria-label="Quitar uno">−</button>
                                        <input type="number" name="cantidad" value="1" min="1" max="{{ min(20, $producto->stock) }}" inputmode="numeric" aria-label="Cantidad de {{ $producto->nombre }}">
                                        <button type="button" data-paso="1" aria-label="Agregar uno">+</button>
                                    </div>
                                    <button type="submit" class="btn">Agregar</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="tarjeta vacio">
            <span class="grande">@include('partials.icono', ['n' => 'buscar', 'tam' => 44, 'grosor' => 1.5])</span>
            @if ($buscar !== '')
                No encontramos productos con “{{ $buscar }}”.
            @else
                Todavía no hay productos en el menú.
            @endif
        </div>
    @endforelse
@endsection

@push('scripts')
<script>
    // Botones − / + de cantidad
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-paso]');
        if (!b) return;
        var input = b.parentElement.querySelector('input');
        var v = (parseInt(input.value, 10) || 1) + parseInt(b.dataset.paso, 10);
        input.value = Math.max(1, Math.min(parseInt(input.max, 10) || 20, v));
    });

    // Marca la categoría que se está viendo
    (function () {
        var nav = document.querySelector('[data-tabs-cat]');
        if (!nav || !('IntersectionObserver' in window)) return;
        var links = nav.querySelectorAll('a[data-cat]');
        function activar(id) {
            links.forEach(function (a) {
                var on = a.dataset.cat === id;
                a.classList.toggle('activo', on);
                if (on) a.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
        }
        var obs = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (en) { if (en.isIntersecting) activar(en.target.id); });
        }, { rootMargin: '-140px 0px -60% 0px' });
        document.querySelectorAll('section.seccion[id]').forEach(function (s) { obs.observe(s); });
        if (links[0]) links[0].classList.add('activo');
    })();
</script>
@endpush
