@extends('layouts.panel')

@section('titulo', 'Mis pedidos')

@section('contenido')
    <h1>Mis pedidos</h1>

    @forelse ($pedidos as $pedido)
        <section class="tarjeta">
            <div class="pedido-cab">
                <div>
                    <strong>Pedido #{{ $pedido->id }}</strong>
                    <span class="ayuda"> · {{ $pedido->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <span class="etiqueta estado-{{ $pedido->estado }}">{{ $pedido->estado_texto }}</span>
            </div>

            @if ($pedido->codigo_recojo)
                <p class="dato">Código de recojo: <span class="codigo">{{ $pedido->codigo_recojo }}</span></p>
            @endif

            @if ($pedido->hora_recojo)
                <p class="dato">Hora de recojo: {{ \Illuminate\Support\Str::substr($pedido->hora_recojo, 0, 5) }}</p>
            @endif

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pedido->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto->nombre }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                            <td>Bs {{ number_format($detalle->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="total">Total: Bs {{ number_format($pedido->total, 2) }}</p>

            <div class="acciones">
                @if ($pedido->estado === 'pendiente')
                    <a class="btn chico" href="{{ route('pedido.actual') }}">Continuar mi pedido</a>
                @endif

                @if ($pedido->factura)
                    <a class="btn chico" href="{{ route('pedido.exito', $pedido) }}">Ver mi compra y factura</a>
                    <a class="btn chico gris" href="{{ route('factura.show', $pedido) }}">Solo la factura</a>
                @endif
            </div>
        </section>
    @empty
        <section class="tarjeta">
            <p>Todavía no tienes pedidos. Cuando hagas tu primer pedido aparecerá aquí.</p>
        </section>
    @endforelse
@endsection