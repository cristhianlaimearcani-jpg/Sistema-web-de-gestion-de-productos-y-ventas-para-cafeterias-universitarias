<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Support\NumeroALetras;
use App\Support\Qr;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function show(Request $request, Pedido $pedido)
    {
        abort_unless($pedido->user_id === $request->user()->id, 403);

        return view('facturas.show', self::datos($pedido));
    }

    // Todo lo que necesita la factura (también la usa la pantalla "Gracias por tu compra")
    public static function datos(Pedido $pedido): array
    {
        $pedido->load('detalles.producto', 'factura', 'user');

        $factura = $pedido->factura;

        abort_unless($factura, 404);

        $qrFactura = Qr::dataUri(implode('|', [
            'CAFEQR',
            $factura->numero_formateado,
            $factura->emitida_en->format('Y-m-d H:i'),
            number_format((float) $factura->total, 2, '.', ''),
            $factura->codigo_verificacion,
        ]));

        return [
            'pedido'    => $pedido,
            'factura'   => $factura,
            'enLetras'  => NumeroALetras::bolivianos($factura->total),
            'qrFactura' => $qrFactura,
        ];
    }
}