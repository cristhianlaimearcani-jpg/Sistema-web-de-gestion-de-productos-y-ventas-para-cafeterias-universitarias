@extends('emails.layout')

@section('contenido')
    @php($enlace = \App\Support\UrlPublica::base(request()).route('soporte.show', $ticket, false))
    <p style="margin:0 0 12px;">Hola, <strong>{{ $usuario->name }}</strong>:</p>
    <p style="margin:0 0 12px;">Respondimos tu consulta <strong>#{{ $ticket->id }} · {{ $ticket->asunto }}</strong>:</p>
    <p style="margin:0 0 16px;background:#f7f3f4;border-radius:10px;padding:12px 14px;white-space:pre-line;">{{ $mensaje }}</p>
    <p style="margin:0 0 6px;">Estado: <strong>{{ $ticket->estado_texto }}</strong></p>
    <p style="text-align:center;margin:20px 0;">
        <a href="{{ $enlace }}" style="background:#7d1126;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;display:inline-block;">Ver la conversación</a>
    </p>
@endsection
