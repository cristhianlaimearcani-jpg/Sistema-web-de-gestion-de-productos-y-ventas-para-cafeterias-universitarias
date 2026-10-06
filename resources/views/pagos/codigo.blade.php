@extends('layouts.panel')

@section('titulo', 'Confirma tu compra')

@section('contenido')
    <section class="tarjeta verificar">
        <div class="icono-correo">✉️</div>
        <h1>¡Recibimos tu pago!</h1>
        <p>
            Enviamos un código de 6 dígitos a <strong>{{ $correo }}</strong>.<br>
            Escríbelo aquí para confirmar tu compra de <strong>Bs {{ number_format($pago->monto, 2) }}</strong>
            (pedido #{{ $pedido->id }}).
        </p>

        <form method="POST" action="{{ route('pago.codigo', $pago) }}">
            @csrf

            <label for="codigo">Código de confirmación</label>
            <input type="text" id="codigo" name="codigo" class="input-codigo"
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>

            <button type="submit" class="btn">Confirmar compra</button>
        </form>

        <form method="POST" action="{{ route('pago.codigo.reenviar', $pago) }}">
            @csrf
            <button type="submit" class="btn gris chico" style="margin-top:12px;">Reenviar código</button>
        </form>

        <p class="ayuda" style="margin-top:16px;">
            ¿No te llega? Revisa la carpeta de spam. El código vence en 10 minutos.
        </p>
    </section>
@endsection