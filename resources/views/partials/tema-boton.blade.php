{{-- Botón sol/luna para cambiar entre modo claro y oscuro --}}
<button type="button" class="tema-boton {{ $clase ?? '' }}" data-tema-boton aria-label="Cambiar a modo oscuro" title="Modo claro / oscuro">
    <svg class="ic-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg>
    <svg class="ic-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z"/></svg>
</button>
@once
<script>
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-tema-boton]');
        if (!b) return;
        var html = document.documentElement;
        var nuevo = html.getAttribute('data-tema') === 'oscuro' ? 'claro' : 'oscuro';
        html.setAttribute('data-tema', nuevo);
        try { localStorage.setItem('cafeqr-tema', nuevo); } catch (err) {}
        document.querySelectorAll('[data-tema-boton]').forEach(function (x) {
            x.setAttribute('aria-label', nuevo === 'oscuro' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        });
    });
    document.addEventListener('DOMContentLoaded', function () {
        var osc = document.documentElement.getAttribute('data-tema') === 'oscuro';
        document.querySelectorAll('[data-tema-boton]').forEach(function (x) {
            x.setAttribute('aria-label', osc ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        });
    });
</script>
@endonce
