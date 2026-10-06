@extends('layouts.panel')

@section('titulo', 'Confirmar pedido')

@section('contenido')
    <h1>Confirmar pedido</h1>

    <div class="columnas">
        <section class="tarjeta">
            <div class="pedido-cab">
                <strong>Pedido #{{ $pedido->id }}</strong>
                <span class="etiqueta estado-pendiente">Pendiente de pago</span>
            </div>
            <p class="ayuda">Revisa tu pedido. Todavía puedes modificarlo antes de pagar.</p>

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

            <p class="total">Total a pagar: Bs {{ number_format($pedido->total, 2) }}</p>

            <div class="acciones">
                <a class="btn gris" href="{{ route('pedido.actual') }}">Modificar pedido</a>
            </div>
        </section>

        <section class="tarjeta">
            <h2>Datos para la factura</h2>

            <form method="POST" action="{{ route('pedido.pagar') }}">
                @csrf

                <label for="razon_social">Nombre o razón social</label>
                <input type="text" id="razon_social" name="razon_social" maxlength="120" required
                       value="{{ old('razon_social', $pedido->razon_social ?: auth()->user()->name) }}">

                <label for="nit_ci">NIT o C.I.</label>
                <input type="text" id="nit_ci" name="nit_ci" maxlength="15" placeholder="Ej.: 1234567"
                       value="{{ old('nit_ci', $pedido->nit_ci === '0' ? '' : $pedido->nit_ci) }}">
                <span class="ayuda">Si no tienes NIT, escribe tu C.I. o déjalo vacío (saldrá 0).</span>

                <p class="dato"><strong>Cliente:</strong> {{ auth()->user()->name }}</p>
                <p class="dato"><strong>Correo:</strong> {{ auth()->user()->email }}</p>
                <p class="ayuda">Cuando el pago llegue, te enviaremos a este correo un código de 6 dígitos para confirmar la compra.</p>

                <button type="submit" class="btn">Pagar con código QR</button>
            </form>
        </section>
    </div>
@endsection