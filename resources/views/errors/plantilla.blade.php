<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · CafeQR</title>
    <link rel="stylesheet" href="{{ asset('css/cafeqr.css') }}?v=11">
    @include('partials.tema-head')
</head>
<body class="fondo-auth">
    <a class="volver-inicio" href="{{ url('/') }}">← Inicio</a>
    @include('partials.tema-boton', ['clase' => 'tema-flotante sobre-foto'])
    <main class="error-caja">
        <div class="caja-marca">
            @include('partials.logo-cafeqr', ['tam' => 38])
            <span>CafeQR</span>
        </div>
        <p class="error-num">@yield('codigo')</p>
        <h1>@yield('titulo')</h1>
        <p class="subtitulo">@yield('mensaje')</p>
        <div class="error-acciones">
            @hasSection('acciones')
                @yield('acciones')
            @else
                <a class="btn" href="{{ url('/') }}">Ir al inicio</a>
                <a class="btn contorno" href="javascript:history.back()">Volver atrás</a>
            @endif
        </div>
    </main>
</body>
</html>
