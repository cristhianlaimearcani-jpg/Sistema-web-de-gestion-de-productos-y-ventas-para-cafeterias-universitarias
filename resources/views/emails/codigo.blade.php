<!DOCTYPE html>
<html lang="es">
<body style="margin:0; padding:24px; background:#f4eef0; font-family:Arial, sans-serif; color:#2b1a1e;">
    <div style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden;">
        <div style="background:#7d1126; color:#ffffff; padding:18px 24px; font-size:22px; font-weight:bold;">
            ☕ CafeQR
        </div>

        <div style="padding:24px;">
            <p style="margin-top:0;">Hola, <strong>{{ $nombre }}</strong>:</p>
            <p>Recibimos tu pago de <strong>Bs {{ $monto }}</strong> del pedido <strong>#{{ $pedidoId }}</strong>.
               Escribe este código en la página para confirmar tu compra:</p>

            <p style="text-align:center; font-size:38px; font-weight:bold; letter-spacing:10px; color:#7d1126; background:#f8ebee; border-radius:12px; padding:14px; margin:18px 0;">
                {{ $codigo }}
            </p>

            <p>El código vence en <strong>{{ $minutos }} minutos</strong>.</p>
            <p style="color:#6e5a5f; font-size:13px;">Si no hiciste esta compra, ignora este mensaje: nadie puede confirmarla sin este código.</p>
        </div>

        <div style="background:#f8ebee; color:#6e5a5f; padding:12px 24px; font-size:12px; text-align:center;">
            CafeQR · Universidad Privada del Valle · La Paz, Bolivia
        </div>
    </div>
</body>
</html>