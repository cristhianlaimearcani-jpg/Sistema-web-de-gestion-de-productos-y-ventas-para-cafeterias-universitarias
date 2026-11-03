<script>
    // Botones protegidos: al enviar un formulario, sus botones se desactivan y muestran "Procesando…".
    // Así un doble clic no manda la compra (ni ninguna otra acción) dos veces.
    (function () {
        function botonesDe(form) {
            var lista = Array.prototype.slice.call(form.querySelectorAll('button[type=submit], button:not([type]), input[type=submit]'));
            if (form.id) {
                lista = lista.concat(Array.prototype.slice.call(document.querySelectorAll('button[form="' + form.id + '"], input[type=submit][form="' + form.id + '"]')));
            }
            return lista;
        }
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (e.defaultPrevented || !form || form.hasAttribute('data-sin-proteger')) { return; }
            if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') { return; }
            if (form.dataset.enviando === '1') { e.preventDefault(); return; }
            form.dataset.enviando = '1';
            var botones = botonesDe(form);
            // Se desactivan un instante después, para que el navegador alcance a enviar el formulario.
            setTimeout(function () {
                botones.forEach(function (b) {
                    if (b.disabled) { return; }
                    b.dataset.textoOriginal = b.tagName === 'INPUT' ? b.value : b.innerHTML;
                    b.style.minWidth = b.offsetWidth + 'px';
                    b.disabled = true;
                    b.classList.add('procesando');
                    var texto = b.getAttribute('data-procesando') || 'Procesando…';
                    if (b.tagName === 'INPUT') { b.value = texto; }
                    else { b.innerHTML = '<span class="girar-mini" aria-hidden="true"></span>' + texto; }
                });
            }, 0);
        });
        // Si el usuario vuelve con "Atrás", los botones quedan listos otra vez.
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('form[data-enviando="1"]').forEach(function (form) {
                form.dataset.enviando = '';
                botonesDe(form).forEach(function (b) {
                    if (!b.classList.contains('procesando')) { return; }
                    b.disabled = false;
                    b.classList.remove('procesando');
                    b.style.minWidth = '';
                    if (b.tagName === 'INPUT') { b.value = b.dataset.textoOriginal; } else { b.innerHTML = b.dataset.textoOriginal; }
                });
            });
        });
    })();
</script>
