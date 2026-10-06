@extends('layouts.panel')

@section('titulo', 'Caja')

@section('contenido')
    <h1>Caja de la cafetería</h1>
    <p class="ayuda">
        Aquí confirmas que el dinero llegó a tu cuenta. Al presionar "Recibí el dinero",
        el estudiante recibe el código de confirmación en su correo.
    </p>

    @php
        $grupos = [
            ['titulo' => 'Por verificar', 'pagos' => $porVerificar, 'acciones' => true,
             'vacio' => 'No hay pagos esperando confirmación.'],
            ['titulo' => 'Pendientes (el estudiante todavía está pagando)', 'pagos' => $pendientes, 'acciones' => true,
             'vacio' => 'No hay pagos pendientes.'],
            ['titulo' => 'Recientes', 'pagos' => $recientes, 'acciones' => false,
             'vacio' => 'Todavía no hay pagos confirmados.'],
        ];
    @endphp

    @foreach ($grupos as $grupo)
        <section class="tarjeta">
            <h2>{{ $grupo['titulo'] }} ({{ $grupo['pagos']->count() }})</h2>

            @if ($grupo['pagos']->isEmpty())
                <p class="sin-datos">{{ $grupo['vacio'] }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Estudiante</th>
                            <th>Monto</th>
                            <th>Comprobante</th>
                            <th>Hora</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grupo['pagos'] as $pago)
                            <tr>
                                <td>#{{ $pago->pedido_id }}</td>
                                <td>
                                    {{ $pago->pedido->user->name }}<br>
                                    <span class="ayuda">{{ $pago->pedido->user->email }}</span>
                                </td>
                                <td>Bs {{ number_format($pago->monto, 2) }}</td>
                                <td>{{ $pago->comprobante ?? '—' }}</td>
                                <td>{{ $pago->updated_at->format('d/m H:i') }}</td>
                                <td class="acciones-fila">
                                    @if ($grupo['acciones'])
                                        <form method="POST" action="{{ route('caja.recibido', $pago) }}">
                                            @csrf
                                            <button type="submit" class="btn chico">✅ Recibí el dinero</button>
                                        </form>
                                        <form method="POST" action="{{ route('caja.rechazar', $pago) }}">
                                            @csrf
                                            <button type="submit" class="btn chico rojo">No llegó</button>
                                        </form>
                                    @else
                                        <span class="etiqueta estado-{{ $pago->estado === 'pagado' ? 'entregado' : 'pendiente' }}">
                                            {{ $pago->estado === 'pagado' ? 'Pagado' : 'Esperando código' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endforeach
@endsection