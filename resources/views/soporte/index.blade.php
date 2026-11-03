@extends('layouts.app')

@section('titulo', 'Ayuda y soporte')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Ayuda y soporte</h1>
            <p class="subtitulo" style="margin:0">¿Tuviste un problema al comprar, con un pago o con tu pedido? Aquí lo resolvemos.</p>
        </div>
    </div>

    <div class="soporte-grid">
        <section class="tarjeta">
            <h2>Escríbenos</h2>
            <form method="POST" action="{{ route('soporte.guardar') }}" novalidate>
                @csrf
                <div class="fila-2">
                    <div class="campo {{ $errors->has('tipo') ? 'con-error' : '' }}">
                        <label for="tipo">¿De qué se trata?</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Elige una opción…</option>
                            @foreach (\App\Models\Ticket::TIPOS as $clave => $texto)
                                <option value="{{ $clave }}" @selected(old('tipo', $tipoElegido) === $clave)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('tipo') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="campo {{ $errors->has('pedido_id') ? 'con-error' : '' }}">
                        <label for="pedido_id">Pedido <span class="opcional">(opcional)</span></label>
                        <select id="pedido_id" name="pedido_id">
                            <option value="">Ninguno</option>
                            @foreach ($pedidos as $p)
                                <option value="{{ $p->id }}" @selected((int) old('pedido_id', $pedidoElegido) === $p->id)>#{{ $p->id }} · {{ $p->estado_texto }} · Bs {{ number_format($p->total, 2) }}</option>
                            @endforeach
                        </select>
                        @error('pedido_id') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="campo {{ $errors->has('asunto') ? 'con-error' : '' }}">
                    <label for="asunto">Asunto</label>
                    <input type="text" id="asunto" name="asunto" value="{{ old('asunto') }}" maxlength="120" placeholder="Ej.: Pagué con QR y no me aparece el código" required>
                    @error('asunto') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="campo {{ $errors->has('mensaje') ? 'con-error' : '' }}">
                    <label for="mensaje">Cuéntanos qué pasó</label>
                    <textarea id="mensaje" name="mensaje" maxlength="2000" rows="5" placeholder="Qué hiciste, qué esperabas que pase y qué pasó. Si pagaste, el n.º de transacción del comprobante ayuda mucho." required>{{ old('mensaje') }}</textarea>
                    @error('mensaje') <span class="error">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn">Enviar consulta</button>
                <p class="ayuda" style="margin:10px 0 0">Te respondemos aquí y te avisamos por correo a <strong>{{ auth()->user()->email }}</strong>.</p>
            </form>
        </section>

        <section class="tarjeta">
            <h2>Preguntas frecuentes</h2>
            <div class="faq">
                <details>
                    <summary>Pagué con QR y la pantalla no cambió</summary>
                    <p>Apenas se confirma el pago, la pantalla cambia sola y te muestra tu código de recojo. Espera unos segundos. Si pasan más de 2 minutos, revisa <a href="{{ route('pedidos.historial') }}">Mis pedidos</a>: si sigue “Pendiente de pago”, escríbenos con el número de pedido y el n.º de transacción del comprobante.</p>
                </details>
                <details>
                    <summary>¿Me pueden cobrar dos veces?</summary>
                    <p>No. Cada pago queda unido a su pedido y una misma transacción no se puede acreditar dos veces. Además, si tocas “Pagar” varias veces, se registra un solo pedido. Si igual ves dos cobros, escríbenos y lo revisamos.</p>
                </details>
                <details>
                    <summary>¿Puedo cancelar mi pedido?</summary>
                    <p>Sí, mientras la cafetería no haya empezado a prepararlo: entra a <a href="{{ route('pedidos.historial') }}">Mis pedidos</a> → Seguir mi pedido → Cancelar. Si ya habías pagado, queda un <strong>reembolso pendiente</strong> y te devolvemos el dinero en la caja.</p>
                </details>
                <details>
                    <summary>Perdí mi código de recojo</summary>
                    <p>Lo ves en <a href="{{ route('pedidos.historial') }}">Mis pedidos</a>, en el correo de “Pago confirmado” y en tu factura.</p>
                </details>
                <details>
                    <summary>Un producto dice “Agotado”</summary>
                    <p>Se terminaron las unidades por ahora. Cuando la cafetería reponga, vuelve a aparecer disponible en el menú.</p>
                </details>
                <details>
                    <summary>No me llegan los correos</summary>
                    <p>Revisa las carpetas Spam y Promociones. Igual, el estado de tu pedido y tu código siempre se ven en Mis pedidos.</p>
                </details>
            </div>
        </section>
    </div>

    <section class="tarjeta">
        <h2>Mis consultas</h2>
        @forelse ($tickets as $ticket)
            <a class="ticket-fila" href="{{ route('soporte.show', $ticket) }}">
                <span class="ticket-num">#{{ $ticket->id }}</span>
                <span class="ticket-txt"><strong>{{ $ticket->asunto }}</strong><small>{{ $ticket->tipo_texto }}@if ($ticket->pedido_id) · Pedido #{{ $ticket->pedido_id }}@endif · {{ $ticket->updated_at->timezone(config('app.timezone'))->format('d/m H:i') }} · {{ $ticket->mensajes_count }} {{ $ticket->mensajes_count === 1 ? 'mensaje' : 'mensajes' }}</small></span>
                <span class="etiqueta ticket-{{ $ticket->estado }}">{{ $ticket->estado_texto }}</span>
            </a>
        @empty
            <p class="ayuda">Todavía no enviaste ninguna consulta.</p>
        @endforelse
    </section>
@endsection
