<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Pedido;
use App\Services\Notificador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Caja: confirmar pagos (QR por verificar y efectivo). Empleados y administrador. */
class CajaController extends Controller
{
    public function pagos(): View
    {
        $conPedido = ['pedido.user', 'pedido.detalles'];

        return view('caja.pagos', [
            'porVerificar' => Pago::where('estado', 'por_verificar')->with($conPedido)->oldest()->get(),
            'efectivo' => Pago::where('estado', 'por_cobrar')->with($conPedido)->oldest()->get(),
            'confirmadosHoy' => Pago::where('estado', 'completado')->whereDate('confirmado_en', today())
                ->with('pedido.user')->latest('confirmado_en')->get(),
            'modoPrueba' => Notificador::modoPrueba(),
        ]);
    }

    /**
     * "Recibí el dinero": el pedido queda pagado al instante (con código de recojo y factura).
     * En pagos con QR se anota el n.º de transacción del comprobante: si ya se usó, no se acredita otra vez.
     */
    public function recibido(Request $request, Pago $pago): RedirectResponse
    {
        if (! in_array($pago->estado, ['por_cobrar', 'por_verificar', 'recibido'], true)) {
            return back()->with('error', 'Ese pago ya fue atendido.');
        }
        if ($pago->pedido->estado !== Pedido::PENDIENTE_PAGO) {
            return back()->with('error', 'Ese pedido ya no está pendiente de pago.');
        }

        $transaccion = null;
        if (! $pago->esEfectivo()) {
            $request->merge(['transaccion' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('transaccion')))]);
            $datos = $request->validate([
                'transaccion' => ['required', 'string', 'min:4', 'max:40', 'regex:/^[A-Z0-9-]+$/'],
            ], [
                'transaccion.required' => 'Escribe el n.º de transacción que aparece en el comprobante del banco.',
                'transaccion.min' => 'El n.º de transacción debe tener al menos 4 caracteres.',
                'transaccion.max' => 'El n.º de transacción es muy largo.',
                'transaccion.regex' => 'El n.º de transacción solo puede tener letras, números y guiones.',
            ]);
            $transaccion = $datos['transaccion'];
        }

        $resultado = $pago->completar('caja', $transaccion);

        if ($resultado === Pago::TRANSACCION_REPETIDA) {
            $otro = Pago::where('transaccion', $transaccion)->first();

            return back()->withInput()->with('error', 'La transacción '.$transaccion.' ya se usó para acreditar el pedido #'
                .($otro?->pedido_id ?? '?').'. Un mismo pago no se puede acreditar dos veces.');
        }

        return match ($resultado) {
            Pago::OK => back()->with('exito', 'Pago del pedido #'.$pago->pedido_id.' confirmado. El cliente ya tiene su código de recojo y su factura.'),
            Pago::YA_PAGADO => back()->with('error', 'Ese pedido ya estaba pagado. No se acreditó dos veces.'),
            default => back()->with('error', 'Ese pago ya no está disponible.'),
        };
    }

    public function rechazar(Pago $pago): RedirectResponse
    {
        if (! in_array($pago->estado, ['por_verificar', 'por_cobrar'], true)) {
            return back()->with('error', 'Ese pago ya fue atendido.');
        }

        $pago->rechazar();

        return back()->with('exito', 'Pago rechazado. El cliente puede volver a intentarlo.');
    }
}
