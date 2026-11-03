<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Iniciar sesión') · CafeQR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/cafeqr.css') }}?v=11">
    @include('partials.tema-head')
    {{-- Activa las animaciones de entrada solo si hay JavaScript (sin JS, el formulario se ve igual, sin animar) --}}
    <script>
        (function () {
            var html = document.documentElement;
            html.classList.add('con-animacion');
            // Desde qué lado llega la tarjeta (lo guarda el enlace que se tocó en la pantalla anterior)
            try {
                var lado = sessionStorage.getItem('cafeqr-llega');
                if (lado) { html.setAttribute('data-llega', lado); sessionStorage.removeItem('cafeqr-llega'); }
            } catch (e) {}
        })();
    </script>
</head>
<body class="fondo-auth">
    @include('partials.tema-boton', ['clase' => 'tema-flotante sobre-foto'])
    <main class="auth-escena">
        <div class="caja auth-tarjeta @yield('ancho')" data-auth-tarjeta>
            <div class="caja-marca auth-marca">
                <span class="auth-logo">@include('partials.logo-cafeqr', ['tam' => 48])</span>
                <span class="auth-marca-texto"><strong>CafeQR</strong><small>Cafetería · Univalle</small></span>
            </div>
            @include('partials.mensajes')
            @yield('contenido')
        </div>
    </main>
@include('partials.proteger-js')
@include('partials.auth-animacion')
    @stack('scripts')
</body>
</html>
