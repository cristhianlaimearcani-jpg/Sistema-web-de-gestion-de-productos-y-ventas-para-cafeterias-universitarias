<script>
    // Animación de entrada de las pantallas de acceso (login, registro y recuperar contraseña):
    // la tarjeta sube y aparece, y luego cada parte del formulario entra una tras otra.
    // Al ir del login al registro (o al revés) la tarjeta sale antes de cambiar de página.
    (function () {
        var tarjeta = document.querySelector('[data-auth-tarjeta]');
        if (!tarjeta) { return; }
        var quieto = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Orden de entrada: marca, mensajes, título, subtítulo, cada campo, botón y enlaces de abajo.
        var partes = [];
        Array.prototype.forEach.call(tarjeta.children, function (hijo) {
            if (hijo.tagName === 'FORM') {
                Array.prototype.forEach.call(hijo.children, function (h) {
                    if (h.type !== 'hidden' && h.tagName !== 'INPUT') { partes.push(h); }
                });
            } else {
                partes.push(hijo);
            }
        });
        partes.forEach(function (el, i) {
            el.classList.add('entra');
            el.style.setProperty('--i', Math.min(i, 14));
        });

        // Si el servidor devolvió errores, el primer campo con error se sacude al terminar de entrar.
        var conError = tarjeta.querySelector('.campo.con-error');
        if (conError && !quieto) {
            setTimeout(function () { conError.classList.add('sacudir'); }, 650);
        }

        // Salida suave al cambiar entre login, registro y recuperar contraseña.
        document.addEventListener('click', function (e) {
            var a = e.target.closest ? e.target.closest('a[data-transicion]') : null;
            if (!a || quieto || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) { return; }
            e.preventDefault();
            var atras = a.getAttribute('data-transicion') === 'atras';
            try { sessionStorage.setItem('cafeqr-llega', atras ? 'izq' : 'der'); } catch (err) {}
            tarjeta.classList.add('sale', atras ? 'sale-der' : 'sale-izq');
            setTimeout(function () { window.location.href = a.href; }, 260);
        });
        // Si vuelve con "Atrás", la tarjeta aparece normal.
        window.addEventListener('pageshow', function (ev) {
            if (ev.persisted) { tarjeta.classList.remove('sale', 'sale-izq', 'sale-der'); }
        });
    })();
</script>
