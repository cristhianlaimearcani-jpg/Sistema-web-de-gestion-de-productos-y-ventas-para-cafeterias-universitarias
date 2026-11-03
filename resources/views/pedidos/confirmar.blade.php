@extends('layouts.app')

@section('titulo', 'Último paso')

@section('contenido')
    <h1>Último paso</h1>
    <p class="subtitulo">Pedido #{{ $pedido->id }} · revisa y elige cómo pagar.</p>

    @if ($repetido)
        <div class="aviso-repetido {{ session('repetido_aviso') ? 'resaltado' : '' }}" id="aviso-repetido" role="alert">
            <div class="aviso-repetido-txt">
                <strong>Ya tienes un pedido igual. ¿Deseas ver ese pedido o comprar otro?</strong>
                <p>Pedido #{{ $repetido->id }} · {{ $repetido->estado_texto }} · hecho a las {{ $repetido->created_at->timezone(config('app.timezone'))->format('H:i') }} ·
                    {{ $repetido->detalles->map(fn ($d) => $d->cantidad.' × '.$d->nombre)->implode(', ') }} · Bs {{ number_format($repetido->total, 2) }}</p>
            </div>
            <div class="aviso-repetido-botones">
                <a class="btn" href="{{ $repetido->estado === 'pendiente_pago' ? route('pedido.continuar', ['pedido' => $repetido->id]) : route('pedido.seguimiento', $repetido) }}">Ver ese pedido</a>
                <button type="button" class="btn contorno" data-comprar-otro>Comprar otro</button>
            </div>
        </div>
    @endif

    <div class="checkout">
        <form id="form-pago" method="POST" action="{{ route('pedido.pagar') }}" novalidate>
            @csrf
            <input type="hidden" name="clave_intento" value="{{ $claveIntento }}">
            <input type="hidden" name="repetir" value="{{ old('repetir', '0') }}" id="campo-repetir">

            <section class="tarjeta">
                <h2 class="bloque-titulo">¿Cómo quieres pagar?</h2>
                @php($metodo = old('metodo', 'qr'))
                <div class="opciones-pago" role="radiogroup" aria-label="Forma de pago">
                    <label class="opcion-pago">
                        <input type="radio" name="metodo" value="qr" {{ $metodo === 'qr' ? 'checked' : '' }}>
                        <span class="opcion-contenido">
                            <span class="icono">@include('partials.icono', ['n' => 'celular', 'tam' => 28])</span>
                            <span class="titulo">Pago con QR</span>
                            <span class="detalle">Escanea el código y confirma con el código que te llega al correo.</span>
                        </span>
                    </label>
                    <label class="opcion-pago">
                        <input type="radio" name="metodo" value="efectivo" {{ $metodo === 'efectivo' ? 'checked' : '' }}>
                        <span class="opcion-contenido">
                            <span class="icono">@include('partials.icono', ['n' => 'efectivo', 'tam' => 28])</span>
                            <span class="titulo">Efectivo</span>
                            <span class="detalle">Paga en la caja de la cafetería y recibe tu código de recojo.</span>
                        </span>
                    </label>
                </div>
                @error('metodo') <span class="error">{{ $message }}</span> @enderror
            </section>

            <section class="tarjeta">
                <h2 class="bloque-titulo">Datos de retiro</h2>
                <div class="dato-retiro">
                    <span class="icono">@include('partials.icono', ['n' => 'tienda'])</span>
                    <div><strong>{{ config('cafeqr.emisor.nombre') }}</strong><span>{{ config('cafeqr.emisor.direccion') }}</span></div>
                </div>
                <div class="dato-retiro">
                    <span class="icono">@include('partials.icono', ['n' => 'reloj'])</span>
                    <div><strong>Retiro en local</strong><span>Después de pagar, el tiempo de preparación aproximado es de {{ config('cafeqr.tiempo_preparacion') }} min. Te avisamos por correo cuando esté en preparación y cuando esté listo.</span></div>
                </div>
                <div class="dato-retiro">
                    <span class="icono">@include('partials.icono', ['n' => 'correo'])</span>
                    <div><strong>Te avisaremos a</strong><span>{{ $usuario->email }}{{ $usuario->telefono ? ' · '.$usuario->telefono : '' }}</span></div>
                </div>
            </section>

            <section class="tarjeta">
                <h2 class="bloque-titulo">Datos de facturación <span class="opcional" style="font-size:13px;font-weight:400;color:var(--texto-suave)">(opcional)</span></h2>
                <div class="fila-2">
                    <div class="campo {{ $errors->has('razon_social') ? 'con-error' : '' }}">
                        <label for="razon_social">Nombre o razón social</label>
                        <input type="text" id="razon_social" name="razon_social" value="{{ old('razon_social', $pedido->razon_social) }}" maxlength="80" placeholder="{{ $usuario->name }}">
                        @error('razon_social') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="campo {{ $errors->has('nit_ci') ? 'con-error' : '' }}">
                        <label for="nit_ci">NIT o CI</label>
                        <input type="text" id="nit_ci" name="nit_ci" value="{{ old('nit_ci', $pedido->nit_ci) }}" inputmode="numeric" maxlength="13" placeholder="Solo números" data-solo-numeros>
                        @error('nit_ci') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <p class="ayuda">Si lo dejas vacío, la factura sale a tu nombre y con NIT/CI “S/N”.</p>
            </section>
        </form>

        <aside class="resumen-lateral">
            <section class="tarjeta">
                <h2 class="bloque-titulo">Resumen</h2>
                @foreach ($pedido->detalles as $detalle)
                    <div class="resumen-linea">
                        <span>{{ $detalle->cantidad }} × {{ $detalle->nombre }}</span>
                        <span>Bs {{ number_format($detalle->subtotal, 2) }}</span>
                    </div>
                @endforeach
                @if ($pedido->descuento > 0)
                    <div class="resumen-linea"><span>Subtotal</span><span>Bs {{ number_format($pedido->total + $pedido->descuento, 2) }}</span></div>
                    <div class="resumen-linea"><span>Cupón {{ $pedido->cupon }}</span><span>−Bs {{ number_format($pedido->descuento, 2) }}</span></div>
                @endif
                <div class="resumen-total"><span>Total</span><span>Bs {{ number_format($pedido->total, 2) }}</span></div>

                <div class="cupon-caja">
                    @if ($pedido->cupon)
                        <form method="POST" action="{{ route('pedido.cupon.quitar') }}" class="cupon-form">
                            @csrf @method('DELETE')
                            <span>@include('partials.icono', ['n' => 'cupon', 'tam' => 16]) Cupón <strong>{{ $pedido->cupon }}</strong> aplicado</span>
                            <button type="submit" class="btn contorno chico">Quitar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('pedido.cupon') }}" class="cupon-form">
                            @csrf
                            <input type="text" name="cupon" placeholder="¿Tienes un cupón?" maxlength="20" autocomplete="off" aria-label="Código de cupón">
                            <button type="submit" class="btn contorno chico">Aplicar</button>
                        </form>
                    @endif
                </div>

                <button type="submit" form="form-pago" class="btn block" style="margin-top:16px">Pagar Bs {{ number_format($pedido->total, 2) }}</button>
                <p class="centro" style="margin:12px 0 0"><a href="{{ route('pedido.actual') }}">← Modificar pedido</a></p>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
    @include('partials.validacion-js')
    <script>
        (function () {
            var boton = document.querySelector('[data-comprar-otro]');
            if (!boton) { return; }
            boton.addEventListener('click', function () {
                document.getElementById('campo-repetir').value = '1';
                var aviso = document.getElementById('aviso-repetido');
                aviso.classList.add('elegido');
                aviso.querySelector('.aviso-repetido-txt').innerHTML = '<strong>Listo: vas a comprar otro pedido igual.</strong><p>Elige cómo pagar y pulsa “Pagar”.</p>';
                aviso.querySelector('.aviso-repetido-botones').remove();
            });
        })();
    </script>
@endpush
