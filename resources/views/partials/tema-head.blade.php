{{-- Aplica el tema (claro/oscuro) antes de pintar la página, para que no parpadee --}}
<meta name="color-scheme" content="light dark">
<script>
    (function () {
        var t = null;
        try { t = localStorage.getItem('cafeqr-tema'); } catch (e) {}
        if (t !== 'oscuro' && t !== 'claro') {
            t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'oscuro' : 'claro';
        }
        document.documentElement.setAttribute('data-tema', t);
    })();
</script>
