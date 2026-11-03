@extends('layouts.app')

@section('titulo', 'Consulta #'.$ticket->id)
@section('ancho', 'angosto')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Consulta #{{ $ticket->id }}</h1>
            <p class="subtitulo" style="margin:0">{{ $ticket->asunto }}</p>
        </div>
        <span class="etiqueta ticket-{{ $ticket->estado }}">{{ $ticket->estado_texto }}</span>
    </div>

    <section class="tarjeta">
        <div class="ticket-datos">
            <span><strong>Tipo:</strong> {{ $ticket->tipo_texto }}</span>
            @if ($personal)<span><strong>Cliente:</strong> {{ $ticket->user->name }} · {{ $ticket->user->email }}@if ($ticket->user->telefono) · {{ $ticket->user->telefono }}@endif</span>@endif
            @if ($ticket->pedido)
                <span><strong>Pedido:</strong> #{{ $ticket->pedido->id }} · {{ $ticket->pedido->estado_texto }} · Bs {{ number_format($ticket->pedido->total, 2) }}
                    @if (! $personal && ! in_array($ticket->pedido->estado, ['carrito', 'pendiente_pago']))· <a href="{{ route('pedido.seguimiento', $ticket->pedido) }}">ver pedido</a>@endif
                </span>
            @endif
            <span><strong>Creada:</strong> {{ $ticket->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
        </div>

        <div class="chat">
            @foreach ($ticket->mensajes as $m)
                <div class="chat-msg {{ $m->es_personal ? 'personal' : 'cliente' }}">
                    <div class="chat-quien">{{ $m->es_personal ? 'Cafetería CafeQR' : ($m->user?->name ?? 'Cliente') }} · {{ $m->created_at->timezone(config('app.timezone'))->format('d/m H:i') }}</div>
                    <div class="chat-burbuja">{{ $m->mensaje }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="tarjeta">
        <h2>{{ $personal ? 'Responder al cliente' : 'Escribir un mensaje' }}</h2>
        <form method="POST" action="{{ route('soporte.responder', $ticket) }}" novalidate>
            @csrf
            <div class="campo {{ $errors->has('mensaje') ? 'con-error' : '' }}">
                <label for="mensaje" class="oculto">Mensaje</label>
                <textarea id="mensaje" name="mensaje" rows="4" maxlength="2000" placeholder="{{ $personal ? 'Escribe la respuesta para el cliente' : 'Agrega más información o responde' }}">{{ old('mensaje') }}</textarea>
                @error('mensaje') <span class="error">{{ $message }}</span> @enderror
            </div>
            @if ($personal)
                <div class="campo" style="max-width:260px">
                    <label for="estado">Estado de la consulta</label>
                    <select id="estado" name="estado">
                        @foreach (\App\Models\Ticket::ESTADOS as $clave => $texto)
                            <option value="{{ $clave }}" @selected(($ticket->estado === 'abierto' ? 'en_proceso' : $ticket->estado) === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="acciones">
                <button type="submit" class="btn">{{ $personal ? 'Guardar y avisar al cliente' : 'Enviar mensaje' }}</button>
                <a class="btn contorno" href="{{ $personal ? route('soporte.gestion') : route('soporte.index') }}">Volver</a>
            </div>
        </form>

        @if (! $personal && $ticket->estado !== 'resuelto')
            <form method="POST" action="{{ route('soporte.resolver', $ticket) }}" style="margin-top:12px">
                @csrf
                <button type="submit" class="btn chico verde">Ya se resolvió mi problema</button>
            </form>
        @endif
    </section>
@endsection
