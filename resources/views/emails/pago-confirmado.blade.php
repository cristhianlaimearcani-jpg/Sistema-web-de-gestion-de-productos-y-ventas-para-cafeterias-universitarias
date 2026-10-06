<!DOCTYPE html>
<html lang="es">
<body style="margin:0; padding:24px; background:#f4eef0; font-family:Arial, sans-serif; color:#2b1a1e;">
    <div style="max-width:520px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden;">
        <div style="background:#7d1126; color:#ffffff; padding:18px 24px; font-size:22px; font-weight:bold;">
            ☕ CafeQR · ¡Gracias por tu compra!
        </div>

        <div style="padding:24px;">
            <p style="margin-top:0;">Hola, <strong>{{ $pedido->user->name }}</strong>:</p>
            <p>Tu compra del pedido <strong>#{{ $pedido->id }}</strong> está confirmada. Presenta este código en la cafetería para recogerla:</p>

            <p style="text-align:center; font-size:40px; font-weight:bold; letter-spacing:8px; color:#7d1126; background:#f8ebee; border:2px dashed #7d1126; border-radius:12px; padding:14px; margin:18px 0;">
                {{ $pedido->codigo_recojo }}
            </p>

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                <tr style="background:#f8ebee; color:#560a1a;">
                    <th align="left">Producto</th>
                    <th align="center">Cant.</th>
                    <th align="right">Subtotal</th>
                </tr>
                @foreach ($pedido->detalles as $detalle)
                    <tr>
                        <td style="border-bottom:1px solid #e6d9dc;">{{ $detalle->producto->nombre }}</td>
                        <td align="center" style="border-bottom:1px solid #e6d9dc;">{{ $detalle->cantidad }}</td>
                        <td align="right" style="border-bottom:1px solid #e6d9dc;">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" align="right"><strong>Total</strong></td>
                    <td align="right"><strong style="color:#7d1126; font-size:16px;">Bs {{ number_format($pedido->total, 2) }}</strong></td>
                </tr>
            </table>

            @if ($pedido->factura)
                <div style="margin-top:20px; border:1px solid #e6d9dc; border-radius:10px; padding:14px; font-size:14px;">
                    <strong style="color:#560a1a;">FACTURA N° {{ $pedido->factura->numero_formateado }}</strong><br>
                    Fecha: {{ $pedido->factura->emitida_en->format('d/m/Y H:i') }}<br>
                    Nombre / Razón social: {{ $pedido->factura->razon_social }}<br>
                    NIT / C.I.: {{ $pedido->factura->nit_ci }}<br>
                    Cliente: {{ $pedido->user->name }} · {{ $pedido->user->email }}<br>
                    Código de verificación: {{ $pedido->factura->codigo_verificacion }}
                </div>
                <p style="color:#6e5a5f; font-size:13px;">Puedes ver e imprimir tu factura completa entrando a CafeQR, en "Mis pedidos". Es un documento referencial del sistema.</p>
            @endif
        </div>

        <div style="background:#f8ebee; color:#6e5a5f; padding:12px 24px; font-size:12px; text-align:center;">
            CafeQR · Universidad Privada del Valle · La Paz, Bolivia
        </div>
    </div>
</body>
</html>