<section class="tarjeta factura">
    <header class="factura-cab">
        <div>
            <h2>FACTURA</h2>
            <p class="dato"><strong>N° {{ $factura->numero_formateado }}</strong></p>
            <p class="ayuda">Código de verificación: {{ $factura->codigo_verificacion }}</p>
        </div>
        <div class="factura-emisor">
            <strong>{{ config('cafeqr.emisor_nombre') }}</strong><br>
            NIT: {{ config('cafeqr.emisor_nit') }}<br>
            {{ config('cafeqr.emisor_direccion') }}
        </div>
    </header>

    <div class="factura-datos">
        <div><strong>Fecha de emisión:</strong> {{ $factura->emitida_en->format('d/m/Y H:i') }}</div>
        <div><strong>Pedido:</strong> #{{ $pedido->id }}</div>
        <div><strong>Nombre / Razón social:</strong> {{ $factura->razon_social }}</div>
        <div><strong>NIT / C.I.:</strong> {{ $factura->nit_ci }}</div>
        <div><strong>Forma de pago:</strong> Código QR</div>
        <div><strong>Código de recojo:</strong> {{ $pedido->codigo_recojo }}</div>
    </div>

    <div class="factura-cliente">
        <h3>Datos del cliente</h3>
        <div class="factura-datos">
            <div><strong>Nombre:</strong> {{ $pedido->user->name }}</div>
            <div><strong>Correo:</strong> {{ $pedido->user->email }}</div>
            <div><strong>Teléfono:</strong> {{ $pedido->user->telefono ?? 'No registrado' }}</div>
            <div><strong>Tipo de usuario:</strong> {{ ucfirst($pedido->user->rol) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cant.</th>
                <th>Descripción</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td>Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td>Bs {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">TOTAL: Bs {{ number_format($factura->total, 2) }}</p>
    <p class="en-letras">{{ $enLetras }}</p>

    <div class="factura-pie">
        <p class="nota">
            Documento referencial generado por el sistema CafeQR.<br>
            No reemplaza la factura oficial del Servicio de Impuestos Nacionales.
        </p>
        <img src="{{ $qrFactura }}" alt="QR de la factura" width="130" height="130">
    </div>
</section>