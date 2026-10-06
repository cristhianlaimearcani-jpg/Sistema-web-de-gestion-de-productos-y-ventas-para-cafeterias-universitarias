@extends('layouts.panel')

@section('titulo', 'Mi pedido')

@section('contenido')
    <h1>Mi pedido</h1>

    @if (! $pedido || $pedido->detalles->isEmpty())
        <section class="tarjeta vacio">
            <p>Todavía no agregaste productos a tu pedido.</p>
            <a class="btn" href="{{ route('dashboard') }}">Ver el menú</a>
        </section>
    @else
        <section class="tarjeta">
            <div class="pedido-cab">
                <strong>Pedido #{{ $pedido->id }}</strong>
                <span class="etiqueta estado-pendiente">Pendiente de pago</span>
            </div>
            <p class="ayuda">Puedes modificar tu pedido hasta que lo pagues.</p>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pedido->detalles as $detalle)
                        <tr>
                            <td>
                                {{ $detalle->producto->nombre }}
                                @unless ($detalle->producto->disponible)
                                    <span class="etiqueta agotado">Agotado</span>
                                @endunless
                            </td>
                            <td>Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('pedido.actualizar', $detalle) }}" class="cant-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="cantidad" value="{{ $detalle->cantidad }}" min="1" max="20">
                                    <button type="submit" class="btn chico">Actualizar</button>
                                </form>
                            </td>
                            <td>Bs {{ number_format($detalle->subtotal, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('pedido.quitar', $detalle) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn chico rojo">Quitar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="total">Total: Bs {{ number_format($pedido->total, 2) }}</p>

            <div class="acciones">
                <a class="btn gris" href="{{ route('dashboard') }}">Seguir comprando</a>
                <a class="btn" href="{{ route('pedido.confirmar') }}">Confirmar pedido</a>

                <form method="POST" action="{{ route('pedido.vaciar') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn rojo">Cancelar pedido</button>
                </form>
            </div>
        </section>
    @endif
@endsection