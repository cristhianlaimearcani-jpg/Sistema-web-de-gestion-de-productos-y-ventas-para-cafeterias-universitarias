<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'CafeQR') · CafeQR</title>
    <link rel="stylesheet" href="{{ asset('css/cafeqr.css') }}?v={{ filemtime(public_path('css/cafeqr.css')) }}">
</head>
<body class="fondo-auth">
    <div class="auth-envoltorio">
        <div class="auth-marca">
            @include('partials.logo-univalle')
            <p class="auth-lema">Pedidos anticipados y recojo con código QR</p>
        </div>

        <main class="caja">
            <div class="caja-marca">
                @include('partials.logo-cafeqr', ['tam' => 46])
                <span>CafeQR</span>
            </div>

            @if (session('status'))
                <div class="exito">{{ session('status') }}</div>
            @endif

            @yield('contenido')
        </main>
    </div>
</body>
</html>