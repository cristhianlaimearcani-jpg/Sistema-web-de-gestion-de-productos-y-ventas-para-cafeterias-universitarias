<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'CafeQR') · CafeQR</title>
    <link rel="stylesheet" href="{{ asset('css/cafeqr.css') }}?v={{ filemtime(public_path('css/cafeqr.css')) }}">
</head>
<body>
    @php
        $pedidoCarrito = auth()->user()->pedidoPendiente();
        $itemsCarrito  = $pedidoCarrito ? $pedidoCarrito->detalles->sum('cantidad') : 0;
    @endphp

    <header class="barra">
        <div class="barra-izq">
            <a class="marca" href="{{ route('dashboard') }}">
                @include('partials.logo-cafeqr', ['tam' => 38])
                <span>CafeQR</span>
            </a>
            <a href="{{ route('dashboard') }}">Inicio</a>
            <a href="{{ route('pedidos.historial') }}">Mis pedidos</a>
            <a class="carrito-link" href="{{ route('pedido.actual') }}">🛒 Mi pedido ({{ $itemsCarrito }})</a>
        </div>

        <details class="perfil">
            <summary>
                @include('partials.avatar', ['user' => auth()->user(), 'tam' => 40])
                <span class="nombre">{{ auth()->user()->name }}</span>
            </summary>
            <div class="menu">
                <a href="{{ route('perfil.show') }}">Mi perfil</a>
                <a href="{{ route('pedidos.historial') }}">Mis pedidos</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="salir">Cerrar sesión</button>
                </form>
            </div>
        </details>
    </header>

    <main class="contenedor">
        @if (session('status'))
            <div class="exito">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="alerta">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alerta">{{ $errors->first() }}</div>
        @endif

        @yield('contenido')
    </main>

    <footer class="pie-pagina">CafeQR · Universidad Privada del Valle · La Paz, Bolivia</footer>

    @stack('scripts')
</body>
</html>