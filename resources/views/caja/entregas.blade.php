@extends('layouts.panel')

@section('titulo', 'Entregas')

@section('contenido')
    <h1>Entregas</h1>
    <p class="ayuda">
        Pídele al estudiante su código de recojo, escríbelo aquí y entrega el pedido.
        Al marcarlo "Listo para recoger", el estudiante recibe un aviso en su correo y en su pantalla.
    </p>

    <section class="tarjeta">
        <form method="GET" action="{{ route('caja.entregas') }}" class="buscador-codigo">
            <div>
                <label for="codigo">Código de recojo</label>
                <input type="text" id="codigo" name="codigo" value="{{ $codigo }}" maxlength="6"
                       placeholder="ABC123" autocomplete="off" autofocus>
            </div>
            <button type="submit" class="btn">Buscar pedido</button>
        </form>

        @if ($codigo !== '')
            @if ($encontrado)
                <div class="resumen-banco">
                    <div class="pedido-cab">
                        <strong>Pedido #{{ $encontrado->id }} · {{ $encontrado->user->name }}</strong>
                        <span class="etiqueta estado-{{ $encontrado->estado }}">{{ $encontrado->estado_texto }}</span>
                    </div>
                    <p class="dato">
                        @foreach ($encontrado->detalles as $detalle)
                            {{ $detalle->cantidad }} × {{ $detalle->producto->nombre }}@if (! $loop->last), @endif
                        @endforeach
                    </p>
                    <p class="dato"><strong>Total pagado:</strong> Bs {{ number_format($encontrado->total, 2) }}</p>

                    @if ($encontrado->estado === 'entregado')
                        <div class="exito">Este pedido ya fue entregado.</div>
                    @else
                        <div class="acciones acciones-fila">
                            @include('caja._acciones', ['pedido' => $encontrado])
                        </div>
                    @endif
                </div>
            @else
                <div class="alerta" style="margin-top:14px;">
                    No existe ningún pedido pagado con el código <strong>{{ $codigo }}</strong>.
                </div>
            @endif
        @endif
    </section>

    <section class="tarjeta">
        <h2>Pedidos por entregar ({{ $activos->count() }})</h2>

        @if ($activos->isEmpty())
            <p class="sin-datos">No hay pedidos pendientes de entrega.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Estudiante</th>
                        <th>Código</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activos as $pedido)
                        <tr>
                            <td>#{{ $pedido->id }}</td>
                            <td>{{ $pedido->user->name }}</td>
                            <td><span class="codigo">{{ $pedido->codigo_recojo }}</span></td>
                            <td>
                                @foreach ($pedido->detalles as $detalle)
                                    {{ $detalle->cantidad }} × {{ $detalle->producto->nombre }}@if (! $loop->last), @endif
                                @endforeach
                            </td>
                            <td><span class="etiqueta estado-{{ $pedido->estado }}">{{ $pedido->estado_texto }}</span></td>
                            <td class="acciones-fila">
                                @include('caja._acciones', ['pedido' => $pedido])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="tarjeta">
        <h2>Entregados hoy ({{ $entregadosHoy->count() }})</h2>

        @if ($entregadosHoy->isEmpty())
            <p class="sin-datos">Todavía no se entregó ningún pedido hoy.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Estudiante</th>
                        <th>Código</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entregadosHoy as $pedido)
                        <tr>
                            <td>#{{ $pedido->id }}</td>
                            <td>{{ $pedido->user->name }}</td>
                            <td><span class="codigo">{{ $pedido->codigo_recojo }}</span></td>
                            <td>{{ $pedido->updated_at->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection