@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Productos</h1>
            <p class="subtitulo" style="margin:0">Agrega, edita, quita o marca como agotado lo que se vende, y controla cuántas unidades quedan de cada uno.</p>
        </div>
        <a class="btn" href="{{ route('productos.create') }}">+ Nuevo producto</a>
    </div>

    <div class="kpis kpis-stock">
        <a class="kpi" href="{{ route('productos.index') }}"><div class="num">{{ $resumen['productos'] }}</div><div class="rotulo">Productos en el menú</div></a>
        <a class="kpi verde-kpi" href="{{ route('productos.index', ['estado' => 'disponibles']) }}"><div class="num">{{ number_format($resumen['unidades']) }}</div><div class="rotulo">Unidades en stock</div></a>
        <a class="kpi ambar-kpi" href="{{ route('productos.index', ['estado' => 'stock_bajo']) }}"><div class="num">{{ $resumen['bajos'] }}</div><div class="rotulo">Con stock bajo (≤ {{ \App\Models\Producto::STOCK_BAJO }})</div></a>
        <a class="kpi rojo-kpi" href="{{ route('productos.index', ['estado' => 'agotados']) }}"><div class="num">{{ $resumen['agotados'] }}</div><div class="rotulo">Agotados</div></a>
    </div>

    <details class="tarjeta">
        <summary style="cursor:pointer;font-weight:650">@include('partials.icono', ['n' => 'camara', 'tam' => 18]) Poner fotos a muchos productos de una vez</summary>
        <p class="ayuda" style="margin-top:10px">Copia las fotos (JPG, PNG o WEBP) a la carpeta <code>public\productos</code> del proyecto, con el nombre que ves abajo. No hace falta editar nada: aparecen solas en el menú. Si prefieres una por una, usa <strong>Editar</strong>.</p>
        <p class="ayuda">Ejemplos: «Café americano» → <code>cafe-americano.jpg</code>; «Salteña» → <code>saltena.png</code>. Se recomienda foto horizontal, de menos de 2 MB.</p>
    </details>

    <section class="tarjeta">
        <form class="filtros" method="GET" action="{{ route('productos.index') }}">
            <div class="campo">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="{{ $buscar }}" placeholder="Nombre del producto" maxlength="60">
            </div>
            <div class="campo">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="todas">Todas</option>
                    @foreach (\App\Models\Producto::CATEGORIAS as $cat => $icono)
                        <option value="{{ $cat }}" {{ $categoria === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="disponibles" {{ $estado === 'disponibles' ? 'selected' : '' }}>Disponibles</option>
                    <option value="stock_bajo" {{ $estado === 'stock_bajo' ? 'selected' : '' }}>Stock bajo</option>
                    <option value="agotados" {{ $estado === 'agotados' ? 'selected' : '' }}>Agotados</option>
                </select>
            </div>
            <button type="submit" class="btn">Filtrar</button>
        </form>

        @if ($productos->count())
            <div class="tabla-wrap">
                <table class="tabla-productos">
                    <thead>
                        <tr><th>N.º</th><th></th><th>Producto</th><th>Categoría</th><th class="num">Precio</th><th>Stock</th><th class="num">Vendidos</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($productos as $producto)
                            @php
                                $nivel = ! $producto->disponible ? 'oculto' : ($producto->stock <= 0 ? 'cero' : ($producto->stock_bajo ? 'bajo' : 'ok'));
                            @endphp
                            <tr>
                                <td class="num-fila">{{ $productos->firstItem() + $loop->index }}</td>
                                <td>
                                    @if ($producto->imagen_url)
                                        <img class="prod-thumb" src="{{ $producto->imagen_url }}" alt="">
                                    @else
                                        <span class="prod-thumb">{{ $producto->icono }}</span>
                                    @endif
                                </td>
                                <td><strong>{{ $producto->nombre }}</strong><br><span class="ayuda" title="Nombre de archivo para la foto">@include('partials.icono', ['n' => 'camara', 'tam' => 13]) {{ $producto->nombre_archivo_foto }}.jpg</span>@unless ($producto->tiene_foto) <span class="etiqueta sin-foto">Sin foto real</span>@endunless</td>
                                <td>{{ $producto->categoria }}</td>
                                <td class="num">Bs {{ number_format($producto->precio, 2) }}</td>
                                <td>
                                    <div class="stock-celda">
                                        <span class="stock-num stock-{{ $nivel }}">{{ $producto->stock }}</span>
                                        <form method="POST" action="{{ route('productos.stock', $producto) }}" class="stock-form">
                                            @csrf
                                            <input type="number" name="stock" value="{{ $producto->stock }}" min="0" max="9999" aria-label="Stock de {{ $producto->nombre }}">
                                            <button type="submit" class="btn chico contorno" name="accion" value="fijar">Guardar</button>
                                        </form>
                                        <form method="POST" action="{{ route('productos.stock', $producto) }}">
                                            @csrf
                                            <input type="hidden" name="stock" value="10">
                                            <button type="submit" class="btn chico gris" name="accion" value="sumar" title="Sumar 10 unidades">+10</button>
                                        </form>
                                    </div>
                                </td>
                                <td class="num">{{ (int) ($vendidos[$producto->id] ?? 0) }}</td>
                                <td>
                                    @if ($nivel === 'oculto')
                                        <span class="etiqueta agotado">Agotado (manual)</span>
                                    @elseif ($nivel === 'cero')
                                        <span class="etiqueta agotado">Sin stock</span>
                                    @elseif ($nivel === 'bajo')
                                        <span class="etiqueta stock-bajo">Stock bajo</span>
                                    @else
                                        <span class="etiqueta disponible">Disponible</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="acciones-fila">
                                        <form method="POST" action="{{ route('productos.alternar', $producto) }}">
                                            @csrf
                                            <button type="submit" class="btn chico gris">{{ $producto->disponible ? 'Marcar agotado' : 'Marcar disponible' }}</button>
                                        </form>
                                        <a class="btn chico contorno" href="{{ route('productos.edit', $producto) }}">Editar</a>
                                        <form method="POST" action="{{ route('productos.destroy', $producto) }}" onsubmit="return confirm('¿Quitar «{{ addslashes($producto->nombre) }}» del menú?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn chico rojo">Quitar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $productos->links() }}
        @else
            <div class="vacio"><span class="grande">@include('partials.icono', ['n' => 'plato', 'tam' => 44, 'grosor' => 1.5])</span>No hay productos con esos filtros.</div>
        @endif
    </section>
@endsection
