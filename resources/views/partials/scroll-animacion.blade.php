<script>
    // Animaciones al hacer scroll (páginas internas):
    // - las tarjetas, productos y secciones aparecen suavemente cuando entran en pantalla;
    // - la foto de la U del menú se mueve un poco más lento que la página (parallax);
    // - la barra de arriba gana sombra al bajar.
    // Sin JavaScript o con "reducir movimiento" activado, todo se ve normal y quieto.
    (function () {
        var quieto = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var barra = document.querySelector('.barra');
        var hero = document.querySelector('.menu-hero');
        var pendiente = false;
        function alBajar() {
            pendiente = false;
            var y = window.scrollY || 0;
            if (barra) { barra.classList.toggle('con-sombra', y > 6); }
            if (hero && !quieto) { hero.style.setProperty('--hero-y', Math.min(70, 40 + y * 0.06).toFixed(1) + '%'); }
        }
        window.addEventListener('scroll', function () {
            if (!pendiente) { pendiente = true; window.requestAnimationFrame(alBajar); }
        }, { passive: true });
        alBajar();

        if (quieto || !('IntersectionObserver' in window)) { return; }
        var selector = '.menu-hero, .tabs-cat, .seccion-titulo, .producto, .tarjeta, .kpi, .pedido-card';
        var elementos = Array.prototype.slice.call(document.querySelectorAll(selector))
            .filter(function (el) { return !el.closest('[data-sin-animar]') && !el.parentElement.closest('.revelar-grupo'); });
        var alto = window.innerHeight || 800;

        function terminar(el) {
            el.addEventListener('animationend', function fin() {
                el.classList.remove('revelar', 'visto');
                el.style.removeProperty('--d');
                el.removeEventListener('animationend', fin);
            });
        }
        var observador = new IntersectionObserver(function (entradas) {
            var n = 0;
            entradas.forEach(function (e) {
                if (!e.isIntersecting) { return; }
                e.target.style.setProperty('--d', n++ % 6);
                e.target.classList.add('visto');
                terminar(e.target);
                observador.unobserve(e.target);
            });
        }, { rootMargin: '0px 0px 6% 0px', threshold: 0.01 });

        var enPantalla = 0;
        elementos.forEach(function (el) {
            var r = el.getBoundingClientRect();
            el.classList.add('revelar');
            if (r.top < alto && r.bottom > 0) {
                // Lo que ya se ve al abrir la página entra de una vez, en cascada
                el.style.setProperty('--d', Math.min(enPantalla++, 10));
                el.classList.add('visto');
                terminar(el);
            } else {
                observador.observe(el);
            }
        });
        // Al imprimir (por ejemplo la factura) todo debe verse
        window.addEventListener('beforeprint', function () {
            elementos.forEach(function (el) { el.classList.add('visto'); });
        });
    })();
</script>
