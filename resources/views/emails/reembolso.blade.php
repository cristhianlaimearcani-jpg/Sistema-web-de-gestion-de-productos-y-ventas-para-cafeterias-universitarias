@extends('emails.layout')

@section('contenido')
    <p style="margin:0 0 12px;">Hola, <strong>{{ $usuario->name }}</strong>:</p>
    <p style="margin:0 0 12px;">Registramos la <strong style="color:#166534;">devolución de Bs {{ number_format($pedido->total, 2) }}</strong> de tu pedido cancelado <strong>#{{ $pedido->id }}</strong>.</p>
    <p style="margin:0 0 16px;color:#6e5a5f;">Si no recibiste tu dinero, responde desde CafeQR → Ayuda y lo revisamos.</p>
@endsection
