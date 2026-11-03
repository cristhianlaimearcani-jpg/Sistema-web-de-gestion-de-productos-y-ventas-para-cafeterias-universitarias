<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Pedido;
use App\Support\Qr;
use App\Support\UrlPublica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PagoController extends Controller
{
    /** Pantalla de pago: QR o efectivo, según lo que eligió el cliente. */
    public function mostrar(Request $request, Pago $pago): View|RedirectResponse
    {
        $pedido = $this->pedidoDelCliente($request, $pago);
        $pago->refrescarVencimiento();

        if ($destino = $this->destinoSiYaNoAplica($pedido, $pago)) {
            return $destino;
        }

        if ($pago->esEfectivo()) {
            return view('pagos.efectivo', ['pago' => $pago, 'pedido' => $pedido->load('detalles')]);
        }

        $modoBanco = config('cafeqr.pago_modo') === 'banco';
        $qr = null;
        $urlQr = null;
        $qrBancoDisponible = false;

        if ($modoBanco) {
            $qrBancoDisponible = is_file(public_path(config('cafeqr.qr_cobro')));
        } elseif ($pago->estado === 'pendiente') {
            $urlQr = UrlPublica::base($request).route('pagar.escanear', $pago->referencia, false);
            $qr = Qr::dataUri($urlQr);
        }

        return view('pagos.qr', [
            'pago' => $pago,
            'pedido' => $pedido->load('detalles'),
            'modoBanco' => $modoBanco,
            'qr' => $qr,
            'urlQr' => $urlQr,
            'qrBancoDisponible' => $qrBancoDisponible,
            'enLocalhost' => UrlPublica::esLocal($request->getHost()),
        ]);
    }

    /** La pantalla de pago lo consulta cada 3 segundos. */
    public function estado(Request $request, Pago $pago): JsonResponse
    {
        $pedido = $this->pedidoDelCliente($request, $pago);
        $pago->refrescarVencimiento();

        $siguiente = null;
        if (in_array($pedido->estado, [Pedido::PAGADO, Pedido::EN_PREPARACION, Pedido::LISTO, Pedido::ENTREGADO], true)) {
            $siguiente = route('pedido.exito', $pedido);
        } elseif ($pedido->estado === Pedido::CANCELADO) {
            $siguiente = route('pedidos.historial');
        }

        return response()->json(['estado' => $pago->estado, 'siguiente' => $siguiente]);
    }

    /** QR vencido o rechazado: genera un pago nuevo para el mismo pedido. */
    public function reintentar(Request $request, Pago $pago): RedirectResponse
    {
        $pedido = $this->pedidoDelCliente($request, $pago);

        if ($pedido->estado !== Pedido::PENDIENTE_PAGO) {
            return redirect()->route('pedido.actual');
        }

        $nuevo = Pago::iniciarPara($pedido, $pago->metodo);

        return redirect()->route('pago.show', $nuevo)->with('exito', 'Generamos un nuevo pago para tu pedido.');
    }

    /** Modo banco: el cliente avisa que ya pagó; la cafetería lo confirma en Caja. */
    public function yaPague(Request $request, Pago $pago): RedirectResponse
    {
        $this->pedidoDelCliente($request, $pago);
        $pago->refrescarVencimiento();

        if ($pago->estado === 'pendiente' && config('cafeqr.pago_modo') === 'banco') {
            $pago->marcarPorVerificar();
        }

        return redirect()->route('pago.show', $pago);
    }

    /** Modo demo: botón para simular el pago desde la misma computadora. Va directo al código de recojo. */
    public function simular(Request $request, Pago $pago): RedirectResponse
    {
        $pedido = $this->pedidoDelCliente($request, $pago);
        $pago->refrescarVencimiento();

        if (config('cafeqr.pago_modo') !== 'demo' || $pago->esEfectivo()) {
            return redirect()->route('pago.show', $pago);
        }

        return $this->despuesDeCompletar($pago->completar('simulado'), $pedido, $pago);
    }

    /* ---------- Páginas públicas del QR de prueba (se abren desde el celular) ---------- */

    public function escanear(string $referencia): View
    {
        $pago = $this->pagoDemo($referencia);
        $pago->refrescarVencimiento();

        return view('pagos.escanear', ['pago' => $pago->load('pedido.user'), 'pedido' => $pago->pedido]);
    }

    /** El celular confirma el pago: queda pagado al instante (sin código de 6 dígitos). */
    public function ejecutar(string $referencia): RedirectResponse
    {
        $pago = $this->pagoDemo($referencia);
        $pago->refrescarVencimiento();

        if ($pago->estado === 'pendiente') {
            $pago->completar('celular');
        }

        return redirect()->route('pagar.escanear', $referencia);
    }

    /* ---------- Ayudas ---------- */

    private function pagoDemo(string $referencia): Pago
    {
        abort_unless(config('cafeqr.pago_modo') === 'demo', 404);

        return Pago::where('referencia', $referencia)->where('metodo', Pago::METODO_QR)->firstOrFail();
    }

    private function pedidoDelCliente(Request $request, Pago $pago): Pedido
    {
        $pedido = $pago->pedido;
        abort_unless($pedido->user_id === $request->user()->id, 403);

        return $pedido;
    }

    private function destinoSiYaNoAplica(Pedido $pedido, Pago $pago): ?RedirectResponse
    {
        // Pagos de la versión anterior que quedaron esperando el código: se terminan solos.
        if ($pago->estado === 'recibido') {
            $pago->completar('caja');
            $pedido->refresh();
        }
        if (in_array($pedido->estado, [Pedido::PAGADO, Pedido::EN_PREPARACION, Pedido::LISTO, Pedido::ENTREGADO], true)) {
            return redirect()->route('pedido.exito', $pedido);
        }
        if ($pedido->estado === Pedido::CANCELADO) {
            return redirect()->route('pedidos.historial')->with('error', 'Ese pedido fue cancelado.');
        }
        if (in_array($pago->estado, ['cancelado', 'completado'], true) || $pedido->estado !== Pedido::PENDIENTE_PAGO) {
            return redirect()->route('pedido.actual');
        }

        return null;
    }

    private function despuesDeCompletar(string $resultado, Pedido $pedido, Pago $pago): RedirectResponse
    {
        return match ($resultado) {
            Pago::OK => redirect()->route('pedido.exito', $pedido)->with('exito', '¡Pago confirmado! Este es tu código de recojo.'),
            Pago::YA_PAGADO => redirect()->route('pedido.exito', $pedido)->with('exito', 'Este pedido ya estaba pagado. No se cobró dos veces.'),
            default => redirect()->route('pago.show', $pago)->with('error', 'Este pago ya no está disponible. Genera uno nuevo o revisa Mis pedidos.'),
        };
    }
}
