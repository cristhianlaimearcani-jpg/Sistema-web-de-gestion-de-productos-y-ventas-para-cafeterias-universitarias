<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · CafeQR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/cafeqr.css') }}?v=11">
    @include('partials.tema-head')
</head>
<body>
@php($u = auth()->user())

<header class="barra">
    <div class="barra-izq">
        <a class="marca" href="{{ route($u->rutaInicio()) }}">
            @include('partials.logo-cafeqr', ['tam' => 34])
            <span>CafeQR</span>
        </a>

        <nav class="nav-links" aria-label="Navegación principal">
            @if ($u->esCliente())
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'activo' : '' }}">Inicio</a>
                <a href="{{ route('pedidos.historial') }}" class="{{ request()->routeIs('pedidos.historial') ? 'activo' : '' }}">Mis pedidos</a>
                <a href="{{ route('soporte.index') }}" class="{{ request()->routeIs('soporte.*') ? 'activo' : '' }}">Ayuda</a>
            @else
                @php($ticketsPendientes = \Illuminate\Support\Facades\Schema::hasTable('tickets') ? \App\Models\Ticket::pendientes() : 0)
                @if ($u->esAdministrador())
                    <a href="{{ route('admin.panel') }}" class="{{ request()->routeIs('admin.panel') ? 'activo' : '' }}">Panel</a>
                @endif
                <a href="{{ route('entregas.index') }}" class="{{ request()->routeIs('entregas.*') ? 'activo' : '' }}">Entregas</a>
                <a href="{{ route('caja.pagos') }}" class="{{ request()->routeIs('caja.*') ? 'activo' : '' }}">Caja</a>
                <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos.*') ? 'activo' : '' }}">Productos</a>
                @if ($u->esAdministrador())
                    <a href="{{ route('admin.pedidos') }}" class="{{ request()->routeIs('admin.pedidos') ? 'activo' : '' }}">Pedidos</a>
                    <a href="{{ route('admin.facturas') }}" class="{{ request()->routeIs('admin.facturas') ? 'activo' : '' }}">Facturas</a>
                    <a href="{{ route('admin.usuarios') }}" class="{{ request()->routeIs('admin.usuarios*', 'admin.empleado.*') ? 'activo' : '' }}">Usuarios</a>
                    <a href="{{ route('admin.estadisticas') }}" class="{{ request()->routeIs('admin.estadisticas') ? 'activo' : '' }}">Estadísticas</a>
                @endif
                <a href="{{ route('soporte.gestion') }}" class="{{ request()->routeIs('soporte.gestion', 'soporte.show') ? 'activo' : '' }}">Soporte @if ($ticketsPendientes)<span class="contador">{{ $ticketsPendientes }}</span>@endif</a>
            @endif
        </nav>

        @if ($u->esCliente())
            <a class="carrito-link" href="{{ route('pedido.actual') }}">@include('partials.icono', ['n' => 'carrito', 'tam' => 17]) Mi pedido @if (($itemsCarrito ?? 0) > 0)({{ $itemsCarrito }})@endif</a>
        @endif
    </div>

    <div class="barra-der">
    @include('partials.tema-boton')
    <details class="perfil">
        <summary>
            <span class="rol-chip">{{ $u->rol_texto }}</span>
            <span class="nombre">{{ $u->name }}</span>
            @include('partials.avatar', ['user' => $u, 'tam' => 40])
        </summary>
        <div class="menu">
            <a href="{{ route('perfil.show') }}">@include('partials.icono', ['n' => 'usuario', 'tam' => 17]) Mi perfil</a>
            @if ($u->esCliente())
                <a href="{{ route('pedidos.historial') }}">@include('partials.icono', ['n' => 'recibo', 'tam' => 17]) Mis pedidos</a>
                <a href="{{ route('soporte.index') }}">@include('partials.icono', ['n' => 'campana', 'tam' => 17]) Ayuda y soporte</a>
            @else
                <a href="{{ route('dashboard') }}">@include('partials.icono', ['n' => 'taza', 'tam' => 17]) Ver menú</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="salir">Cerrar sesión</button>
            </form>
        </div>
    </details>
    </div>
</header>

@if (! empty($pedidoListo))
    <div class="banner-listo">
        <span>@include('partials.icono', ['n' => 'fiesta', 'tam' => 18]) ¡Tu pedido #{{ $pedidoListo->id }} está listo para recoger! Código: <strong>{{ $pedidoListo->codigo }}</strong></span>
        <a href="{{ route('pedido.seguimiento', $pedidoListo) }}">Ver detalles →</a>
    </div>
@endif

@php($conBarra = $u->esCliente() && request()->routeIs('dashboard') && ($itemsCarrito ?? 0) > 0)
<main class="contenedor @yield('ancho') {{ $conBarra ? 'con-barra-pedido' : '' }}">
    @include('partials.mensajes')
    @yield('contenido')
</main>

@if ($conBarra)
    <a class="barra-pedido" href="{{ route('pedido.actual') }}">
        <span class="bp-n">{{ $itemsCarrito }}</span>
        <span class="bp-txt">Tu pedido<small>{{ $itemsCarrito === 1 ? '1 producto' : $itemsCarrito.' productos' }}</small></span>
        <span class="bp-total">Bs {{ number_format($totalCarrito ?? 0, 2) }}</span>
        <span class="bp-go">Ver pedido</span>
    </a>
@endif

<footer class="pie-pagina">CafeQR · Universidad Privada del Valle · La Paz, Bolivia</footer>

<script>
    // En celular, el menú de arriba se desliza hasta la sección activa
    (function () {
        var activo = document.querySelector('.nav-links a.activo');
        var nav = document.querySelector('.nav-links');
        if (activo && nav && nav.scrollWidth > nav.clientWidth) {
            nav.scrollLeft = activo.offsetLeft - nav.clientWidth / 2 + activo.offsetWidth / 2;
        }
    })();
    // En celular las tablas se muestran como tarjetas: cada dato lleva el nombre de su columna
    document.querySelectorAll('.tabla-wrap table:not(.factura-tabla)').forEach(function (tabla) {
        tabla.classList.add('apilable');
        var titulos = Array.prototype.map.call(tabla.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
        tabla.querySelectorAll('tbody tr').forEach(function (fila) {
            var col = 0;
            Array.prototype.forEach.call(fila.children, function (celda) {
                if (celda.colSpan > 1) { celda.classList.add('celda-completa'); }
                else { celda.setAttribute('data-label', titulos[col] || ''); }
                col += celda.colSpan || 1;
            });
        });
    });
</script>
@include('partials.proteger-js')
@stack('scripts')
</body>
</html>
