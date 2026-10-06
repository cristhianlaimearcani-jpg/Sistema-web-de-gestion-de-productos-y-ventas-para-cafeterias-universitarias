<!DOCTYPE html>
<html lang="es">
<body style="margin:0; padding:24px; background:#f4eef0; font-family:Arial, sans-serif; color:#2b1a1e;">
    <div style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden;">
        <div style="background:#7d1126; color:#ffffff; padding:18px 24px; font-size:22px; font-weight:bold;">
            ☕ CafeQR · ¡Tu pedido está listo!
        </div>

        <div style="padding:24px;">
            <p style="margin-top:0;">Hola, <strong>{{ $pedido->user->name }}</strong>:</p>
            <p>Tu pedido <strong>#{{ $pedido->id }}</strong> ya está <strong>listo para recoger</strong> en la cafetería.
               Muestra este código:</p>

            <p style="text-align:center; font-size:40px; font-weight:bold; letter-spacing:8px; color:#7d1126; background:#f8ebee; border:2px dashed #7d1126; border-radius:12px; padding:14px; margin:18px 0;">
                {{ $pedido->codigo_recojo }}
            </p>

            <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                @foreach ($pedido->detalles as $detalle)
                    <tr>
                        <td style="border-bottom:1px solid #e6d9dc;">{{ $detalle->cantidad }} × {{ $detalle->producto->nombre }}</td>
                        <td align="right" style="border-bottom:1px solid #e6d9dc;">Bs {{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td align="right"><strong>Total pagado</strong></td>
                    <td align="right"><strong style="color:#7d1126;">Bs {{ number_format($pedido->total, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <div style="background:#f8ebee; color:#6e5a5f; padding:12px 24px; font-size:12px; text-align:center;">
            CafeQR · Universidad Privada del Valle · La Paz, Bolivia
        </div>
    </div>
</body>
</html>