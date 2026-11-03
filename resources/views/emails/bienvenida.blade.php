@extends('emails.layout')

@section('contenido')
    <p style="margin:0 0 12px;">Hola, <strong>{{ $usuario->name }}</strong>:</p>
    <p style="margin:0 0 16px;">¡Gracias por registrarte en <strong>CafeQR</strong>! Tu cuenta ya está lista.</p>
    <p style="margin:0 0 8px;">Ahora puedes:</p>
    <ul style="margin:0 0 16px;padding-left:20px;">
        <li>Ver el menú de la cafetería y armar tu pedido.</li>
        <li>Pagar con QR o en efectivo.</li>
        <li>Recoger tu pedido sin hacer fila, con tu código de recojo.</li>
    </ul>
    <p style="margin:0 0 16px;">A este correo te llegarán la confirmación de tus pagos, tu código de recojo y los avisos de tus pedidos.</p>
    <p style="margin:0 0 16px;color:#6e5a5f;">Si tú no creaste esta cuenta, ignora este mensaje.</p>
@endsection
