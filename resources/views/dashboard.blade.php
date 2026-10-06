@extends('layouts.panel')

@section('titulo', 'Inicio')

@section('contenido')
    <section class="hero">
        @include('partials.logo-univalle')
        <h1>Hola, {{ auth()->user()->name }} 👋</h1>
        <p>¿Qué quieres pedir hoy? Arma tu pedido y recógelo sin hacer fila.</p>

        <div class="buscador">
            <input type="search" id="buscador" placeholder="🔍 Buscar un producto..." autocomplete="off">
        </div>
    </section>

    @if ($secciones->isEmpty())
        <section class="tarjeta vacio">
            <p>Todavía no hay productos en el menú.</p>
        </section>
    @else
        <nav class="chips">
            @foreach ($secciones as $nombre => $icono)
                <a href="#{{ \Illuminate\Support\Str::slug($nombre) }}">{{ $icono }} {{ $nombre }}</a>
            @endforeach
        </nav>

        @foreach ($secciones as $nombre => $icono)
            <section class="seccion" id="{{ \Illuminate\Support\Str::slug($nombre) }}">
                <h2 class="seccion-titulo">
                    {{ $icono }} {{ $nombre }}
                    <span class="cuenta">{{ $productos[$nombre]->count() }} productos</span>
                </h2>

                <div class="grilla">
                    @foreach ($productos[$nombre] as $producto)
                        <article class="producto {{ $producto->disponible ? '' : 'sin-stock' }}"
                                 data-nombre="{{ \Illuminate\Support\Str::lower($producto->nombre) }}">
                            <div class="producto-img">
                                @if ($producto->imagen)
                                    <img src="{{ asset($producto->imagen) }}" alt="{{ $producto->nombre }}">
                                @else
                                    {{ $producto->icono }}
                                @endif
                            </div>

                            <div class="producto-cuerpo">
                                <h3>{{ $producto->nombre }}</h3>
                                <p class="desc">{{ $producto->descripcion }}</p>
                                <p class="precio">Bs {{ number_format($producto->precio, 2) }}</p>

                                @if ($producto->disponible)
                                    <form method="POST" action="{{ route('pedido.agregar', $producto) }}" class="agregar">
                                        @csrf
                                        <input type="number" name="cantidad" value="1" min="1" max="10" aria-label="Cantidad">
                                        <button type="submit" class="btn">Agregar</button>
                                    </form>
                                @else
                                    <div><span class="etiqueta agotado">Agotado</span></div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
@endsection

@push('scripts')
<script>
    // Buscador: filtra los productos mientras escribes (ignora tildes)
    (function () {
        var buscador = document.getElementById('buscador');
        if (!buscador) return;

        function normalizar(texto) {
            return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        buscador.addEventListener('input', function () {
            var q = normalizar(buscador.value.trim());

            document.querySelectorAll('.producto').forEach(function (p) {
                p.style.display = normalizar(p.dataset.nombre).indexOf(q) !== -1 ? '' : 'none';
            });

            document.querySelectorAll('.seccion').forEach(function (s) {
                var hayVisibles = Array.prototype.some.call(
                    s.querySelectorAll('.producto'),
                    function (p) { return p.style.display !== 'none'; }
                );
                s.style.display = hayVisibles ? '' : 'none';
            });
        });
    })();
</script>
@endpush