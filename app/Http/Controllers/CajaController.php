<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;

// Pantalla de la cafetería: aquí se confirma que el dinero llegó
class CajaController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizar($request);

        $porVerificar = Pago::with('pedido.user')
            ->where('estado', 'por_verificar')
            ->orderBy('updated_at')
            ->get();

        $pendientes = Pago::with('pedido.user')
            ->where('metodo', 'banco')
            ->where('estado', 'pendiente')
            ->latest('id')
            ->limit(15)
            ->get();

        $recientes = Pago::with('pedido.user')
            ->whereIn('estado', ['recibido', 'pagado'])
            ->latest('id')
            ->limit(15)
            ->get();

        return view('caja.pagos', compact('porVerificar', 'pendientes', 'recientes'));
    }

    // "Recibí el dinero": se envía el código de confirmación al correo del estudiante
    public function recibido(Request $request, Pago $pago)
    {
        $this->autorizar($request);

        if ($pago->marcarRecibido() !== 'recibido') {
            return back()->with('error', 'No se pudo confirmar: el pago está "' . $pago->refresh()->estado . '".');
        }

        $enviado = $pago->enviarCodigo();

        return back()->with(
            'status',
            $enviado
                ? 'Pago confirmado. Se envió el código al correo del estudiante.'
                : 'Pago confirmado, pero el correo no se pudo enviar. El estudiante puede pedir el código otra vez en su pantalla.'
        );
    }

    public function rechazar(Request $request, Pago $pago)
    {
        $this->autorizar($request);

        $pago->fallar('La cafetería no encontró tu pago. Si ya pagaste, habla con la cafetería.');

        return back()->with('status', 'Pago rechazado.');
    }

    private function autorizar(Request $request): void
    {
        abort_unless($request->user()->rol === 'encargado', 403);
    }
}