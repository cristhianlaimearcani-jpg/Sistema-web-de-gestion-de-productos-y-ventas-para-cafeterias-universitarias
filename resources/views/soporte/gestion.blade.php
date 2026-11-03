@extends('layouts.app')

@section('titulo', 'Soporte')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Soporte</h1>
            <p class="subtitulo" style="margin:0">Consultas y problemas de los clientes. Responde y cambia el estado: el cliente recibe un correo.</p>
        </div>
    </div>

    <nav class="filtro-estados" aria-label="Filtrar consultas">
        @php($pend = ($cuentas['abierto'] ?? 0) + ($cuentas['en_proceso'] ?? 0))
        <a href="{{ route('soporte.gestion', ['estado' => 'pendientes']) }}" class="{{ $estado === 'pendientes' ? 'activo' : '' }}">Por atender <small>{{ $pend }}</small></a>
        @foreach (\App\Models\Ticket::ESTADOS as $clave => $texto)
            <a href="{{ route('soporte.gestion', ['estado' => $clave]) }}" class="{{ $estado === $clave ? 'activo' : '' }}">{{ $texto }} <small>{{ $cuentas[$clave] ?? 0 }}</small></a>
        @endforeach
        <a href="{{ route('soporte.gestion', ['estado' => 'todos']) }}" class="{{ $estado === 'todos' ? 'activo' : '' }}">Todas <small>{{ $cuentas->sum() }}</small></a>
    </nav>

    <section class="tarjeta">
        @if ($tickets->count())
            <div class="tabla-wrap">
                <table>
                    <thead><tr><th>N.º</th><th>Cliente</th><th>Asunto</th><th>Tipo</th><th>Pedido</th><th>Actualizada</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td>#{{ $ticket->id }}</td>
                                <td>{{ $ticket->user->name }}<br><span class="ayuda">{{ $ticket->user->email }}</span></td>
                                <td><strong>{{ $ticket->asunto }}</strong><br><span class="ayuda">{{ $ticket->mensajes_count }} {{ $ticket->mensajes_count === 1 ? 'mensaje' : 'mensajes' }}</span></td>
                                <td>{{ $ticket->tipo_texto }}</td>
                                <td>{{ $ticket->pedido_id ? '#'.$ticket->pedido_id : '—' }}</td>
                                <td>{{ $ticket->updated_at->timezone(config('app.timezone'))->format('d/m H:i') }}</td>
                                <td><span class="etiqueta ticket-{{ $ticket->estado }}">{{ $ticket->estado_texto }}</span></td>
                                <td><a class="btn chico" href="{{ route('soporte.show', $ticket) }}">Atender</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $tickets->links() }}
        @else
            <div class="vacio"><span class="grande">@include('partials.icono', ['n' => 'check', 'tam' => 44, 'grosor' => 1.5])</span>No hay consultas en esta lista.</div>
        @endif
    </section>
@endsection
