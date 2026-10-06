@php
    $rutaLogo = collect(['png', 'svg', 'jpg', 'webp'])
        ->map(fn ($ext) => 'img/logo-univalle.' . $ext)
        ->first(fn ($ruta) => file_exists(public_path($ruta)));
@endphp

<div class="sello-univalle">
    @if ($rutaLogo)
        <img class="logo-univalle" src="{{ asset($rutaLogo) }}" alt="Universidad Privada del Valle">
    @else
        <div class="logo-univalle-texto">
            <strong>UNIVALLE</strong>
            <span>Universidad Privada del Valle</span>
        </div>
    @endif
</div>