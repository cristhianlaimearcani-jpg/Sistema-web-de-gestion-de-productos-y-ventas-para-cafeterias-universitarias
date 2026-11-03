@extends('layouts.guest')

@section('titulo', 'Crear cuenta')
@section('ancho', 'ancha')

@section('contenido')
    <h1>Crear cuenta</h1>
    <p class="subtitulo">Regístrate con tu correo institucional para hacer pedidos.</p>

    <form method="POST" action="{{ route('registro.guardar') }}" novalidate>
        @csrf

        <div class="campo {{ $errors->has('name') ? 'con-error' : '' }}">
            <label for="name">Nombre completo</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="60" autocomplete="name"
                   placeholder="Ej.: María Fernández" required autofocus data-solo-letras>
            <p class="requisitos">Solo letras y espacios. No se aceptan números.</p>
            @error('name') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="campo {{ $errors->has('email') ? 'con-error' : '' }}">
            <label for="email">Correo institucional</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="120" autocomplete="email"
                   placeholder="tunombre@est.{{ explode(',', config('cafeqr.dominio'))[0] }}" required>
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="campo {{ $errors->has('telefono') ? 'con-error' : '' }}">
            <label for="telefono">Celular <span class="opcional">(opcional)</span></label>
            <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}" inputmode="numeric" maxlength="8"
                   placeholder="Ej.: 71234567 o 51234567" autocomplete="tel-national" data-solo-numeros data-celular>
            <p class="requisitos" data-celular-ayuda>8 dígitos. Puede empezar con 5, 6 o 7.</p>
            @error('telefono') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="fila-2">
            <div class="campo {{ $errors->has('password') ? 'con-error' : '' }}">
                <label for="password">Contraseña</label>
                <div class="campo-clave">
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required data-clave>
                    <button type="button" class="ver-clave" data-ver-clave aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar / ocultar">
                        <svg class="ic-ojo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="ic-ojo-no" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="campo">
                <label for="password_confirmation">Repite la contraseña</label>
                <div class="campo-clave">
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required data-clave-repetir>
                    <button type="button" class="ver-clave" data-ver-clave aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar / ocultar">
                        <svg class="ic-ojo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="ic-ojo-no" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <ul class="reqs-clave" data-reqs aria-live="polite">
            <li data-req="largo">Mínimo 8 caracteres</li>
            <li data-req="letra">Una letra</li>
            <li data-req="numero">Un número</li>
            <li data-req="igual">Las dos coinciden</li>
        </ul>

        <button type="submit" class="enviar" data-procesando="Creando tu cuenta…">Crear mi cuenta</button>
    </form>

    <p class="pie">¿Ya tienes cuenta? <a href="{{ route('login') }}" data-transicion="atras">Inicia sesión</a></p>
@endsection

@push('scripts')
    @include('partials.validacion-js')
    <script>
        (function () {
            // Ojito: mostrar / ocultar la contraseña
            document.querySelectorAll('[data-ver-clave]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var input = b.parentElement.querySelector('input');
                    var ver = input.type === 'password';
                    input.type = ver ? 'text' : 'password';
                    b.setAttribute('aria-pressed', ver ? 'true' : 'false');
                    b.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
                    b.classList.toggle('activo', ver);
                    input.focus();
                    var fin = input.value.length;
                    try { input.setSelectionRange(fin, fin); } catch (err) {}
                });
            });

            // Requisitos de la contraseña y si las dos coinciden, en vivo
            var clave = document.querySelector('[data-clave]');
            var repetir = document.querySelector('[data-clave-repetir]');
            var reqs = document.querySelector('[data-reqs]');
            function marca(nombre, ok, mostrar) {
                var li = reqs.querySelector('[data-req="' + nombre + '"]');
                li.classList.toggle('ok', mostrar && ok);
                li.classList.toggle('mal', mostrar && !ok);
            }
            function revisar() {
                var v = clave.value, r = repetir.value;
                marca('largo', v.length >= 8, v !== '');
                marca('letra', /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(v), v !== '');
                marca('numero', /\d/.test(v), v !== '');
                marca('igual', v !== '' && v === r, r !== '');
                reqs.querySelector('[data-req="igual"]').textContent = (r !== '' && v !== r) ? 'No coinciden' : 'Las dos coinciden';
                repetir.classList.toggle('invalido', r !== '' && v !== r);
                repetir.classList.toggle('valido', r !== '' && v === r && v.length >= 8);
                return v.length >= 8 && /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(v) && /\d/.test(v) && v === r;
            }
            clave.addEventListener('input', revisar);
            repetir.addEventListener('input', revisar);

            // Celular: 8 dígitos que empiezan con 5, 6 o 7
            var cel = document.querySelector('[data-celular]');
            var celAyuda = document.querySelector('[data-celular-ayuda]');
            function revisarCelular() {
                var v = cel.value;
                var ok = /^[567]\d{7}$/.test(v);
                cel.classList.toggle('invalido', v !== '' && (v.length === 8 || !/^[567]/.test(v)) && !ok);
                cel.classList.toggle('valido', ok);
                if (v === '') { celAyuda.textContent = '8 dígitos. Puede empezar con 5, 6 o 7.'; celAyuda.className = 'requisitos'; }
                else if (!/^[567]/.test(v)) { celAyuda.textContent = 'Debe empezar con 5, 6 o 7.'; celAyuda.className = 'requisitos mal'; }
                else if (!ok) { celAyuda.textContent = 'Faltan ' + (8 - v.length) + ' dígito' + (8 - v.length === 1 ? '' : 's') + '.'; celAyuda.className = 'requisitos'; }
                else { celAyuda.textContent = 'Celular correcto.'; celAyuda.className = 'requisitos ok'; }
                return v === '' || ok;
            }
            cel.addEventListener('input', revisarCelular);

            // No deja enviar si algo está mal (el servidor igual lo vuelve a revisar)
            clave.form.addEventListener('submit', function (e) {
                var celOk = revisarCelular();
                var claveOk = clave.value === '' || revisar();
                if (!celOk || !claveOk) {
                    e.preventDefault();
                    (!celOk ? cel : (repetir.value !== clave.value ? repetir : clave)).focus();
                }
            });
            if (clave.value || repetir.value) revisar();
            if (cel.value) revisarCelular();
        })();
    </script>
@endpush
