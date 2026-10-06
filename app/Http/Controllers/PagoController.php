<?php

namespace App\Http\Controllers;

use App\Mail\PagoConfirmadoMail;
use App\Models\Pago;
use App\Models\Pedido;
use App\Support\Qr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PagoController extends Controller
{
    // ---------- 1. Datos de factura y creación del pago ----------
    public function iniciar(Request $request)
    {
        $datos = $request->validate([
            'razon_social' => ['nullable', 'string', 'max:120'],
            'nit_ci'       => ['nullable', 'string', 'max:15', 'regex:/^[0-9A-Za-z\-\.]*$/'],
        ], [
            'razon_social.max' => 'El nombre o razón social es muy largo.',
            'nit_ci.max'       => 'El NIT o C.I. es muy largo.',
            'nit_ci.regex'     => 'El NIT o C.I. solo puede tener letras, números, guion o punto.',
        ]);

        $pedido = $request->user()->pedidoPendiente();

        if (! $pedido || $pedido->detalles->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'Primero agrega productos a tu pedido.');
        }

        // Si el pago ya está en proceso, se continúa donde se quedó
        $activo = $pedido->pagoActivo();

        if ($activo && in_array($activo->estado, ['por_verificar', 'recibido'], true)) {
            return redirect()->route('pago.show', $activo);
        }

        if ($pedido->detalles->contains(fn ($d) => ! $d->producto->disponible)) {
            return redirect()->route('pedido.actual')->with('error', 'Hay productos agotados en tu pedido. Quítalos para continuar.');
        }

        $pedido->update([
            'razon_social' => trim((string) ($datos['razon_social'] ?? '')) ?: $request->user()->name,
            'nit_ci'       => trim((string) ($datos['nit_ci'] ?? '')) ?: '0',
        ]);

        return redirect()->route('pago.show', Pago::iniciarPara($pedido));
    }

    // ---------- 2. Pantalla del pago: QR, o espera, o código del correo ----------
    public function show(Request $request, Pago $pago)
    {
        $pedido = $this->pedidoDelUsuario($request, $pago);

        $pago->refrescarVencimiento();

        if ($pago->estado === 'pagado') {
            return redirect()->route('pedido.exito', $pedido);
        }

        $pedido->load('detalles.producto');

        // El dinero ya llegó: falta escribir el código que se envió al correo
        if ($pago->estado === 'recibido') {
            return view('pagos.codigo', [
                'pago'   => $pago,
                'pedido' => $pedido,
                'correo' => $this->enmascarar($request->user()->email),
            ]);
        }

        $modoBanco = $pago->metodo === 'banco';
        $qr = null;
        $qrBanco = null;

        if ($pago->estado === 'pendiente') {
            if ($modoBanco) {
                $ruta = config('cafeqr.qr_cobro');
                $qrBanco = file_exists(public_path($ruta))
                    ? asset($ruta) . '?v=' . filemtime(public_path($ruta))
                    : null;
            } else {
                $qr = Qr::dataUri(route('pago.escanear', $pago->referencia));
            }
        }

        $intentos = $pedido->pagos()->orderBy('id')->get();

        return view('pagos.qr', compact('pago', 'pedido', 'modoBanco', 'qr', 'qrBanco', 'intentos'));
    }

    // La pantalla consulta esto cada pocos segundos para actualizarse sola
    public function estado(Request $request, Pago $pago)
    {
        $this->pedidoDelUsuario($request, $pago);

        $pago->refrescarVencimiento();

        return response()->json(['estado' => $pago->estado]);
    }

    // Si el pago falló o venció, se puede intentar de nuevo
    public function reintentar(Request $request, Pago $pago)
    {
        $pedido = $this->pedidoDelUsuario($request, $pago);

        if ($pedido->estado !== 'pendiente') {
            return redirect()->route('pedidos.historial')->with('error', 'Ese pedido ya no admite pagos.');
        }

        return redirect()
            ->route('pago.show', Pago::iniciarPara($pedido))
            ->with('status', 'Generamos un nuevo código QR.');
    }

    // ---------- Modo "banco": el estudiante avisa que ya pagó ----------
    public function yaPague(Request $request, Pago $pago)
    {
        $this->pedidoDelUsuario($request, $pago);

        abort_unless($pago->metodo === 'banco', 404);

        $datos = $request->validate([
            'comprobante' => ['nullable', 'string', 'max:60'],
        ], [
            'comprobante.max' => 'El número de comprobante es muy largo.',
        ]);

        $pago->marcarPorVerificar($datos['comprobante'] ?? null);

        return redirect()
            ->route('pago.show', $pago)
            ->with('status', 'Avisamos a la cafetería. Cuando confirme tu pago te llegará un código a tu correo.');
    }

    // ---------- Modo "demo": botones para simular el pago ----------
    public function simular(Request $request, Pago $pago)
    {
        $this->pedidoDelUsuario($request, $pago);

        abort_unless($pago->metodo === 'demo', 404);

        $resultado = $request->validate([
            'resultado' => ['required', 'in:exito,fallo'],
        ])['resultado'];

        if ($resultado === 'exito') {
            $this->registrarRecibido($pago);
        } else {
            $pago->fallar('El banco rechazó el pago (simulación).');
        }

        return redirect()->route('pago.show', $pago);
    }

    // ---------- 3. Código que llegó al correo ----------
    public function comprobarCodigo(Request $request, Pago $pago)
    {
        $pedido = $this->pedidoDelUsuario($request, $pago);

        $request->validate([
            'codigo' => ['required', 'digits:6'],
        ], [
            'codigo.required' => 'Escribe el código que te enviamos al correo.',
            'codigo.digits'   => 'El código tiene 6 números.',
        ]);

        if ($pago->estado === 'pagado') {
            return redirect()->route('pedido.exito', $pedido);
        }

        if ($pago->estado !== 'recibido') {
            return redirect()->route('pago.show', $pago)->with('error', 'Todavía no recibimos tu pago.');
        }

        $verificacion = $pago->verificaciones()->whereNull('usado_en')->latest('id')->first();

        if (! $verificacion) {
            return back()->withErrors(['codigo' => 'No hay un código activo. Presiona "Reenviar código".']);
        }

        $resultado = $verificacion->comprobar($request->input('codigo'));

        if ($resultado !== 'ok') {
            $mensaje = match ($resultado) {
                'incorrecto' => 'El código no es correcto. Te quedan ' . $verificacion->intentosRestantes() . ' intentos.',
                'bloqueado'  => 'Fallaste demasiadas veces. Presiona "Reenviar código" para recibir uno nuevo.',
                default      => 'El código venció. Presiona "Reenviar código" para recibir uno nuevo.',
            };

            return back()->withErrors(['codigo' => $mensaje]);
        }

        if ($pago->completar() === 'completado') {
            $this->enviarComprobante($pago->pedido_id);
        }

        return redirect()->route('pedido.exito', $pedido)->with('gracias', true);
    }

    public function reenviarCodigo(Request $request, Pago $pago)
    {
        $this->pedidoDelUsuario($request, $pago);

        if ($pago->estado !== 'recibido') {
            return redirect()->route('pago.show', $pago);
        }

        $ultima = $pago->verificaciones()->latest('id')->first();

        if ($ultima && $ultima->created_at->gt(now()->subSeconds(60))) {
            return back()->with('error', 'Espera un minuto antes de pedir otro código.');
        }

        if (! $pago->enviarCodigo()) {
            return back()->with('error', 'No pudimos enviar el correo. Revisa la configuración del correo en el archivo .env.');
        }

        return back()->with('status', 'Te enviamos un código nuevo a ' . $this->enmascarar($request->user()->email) . '.');
    }

    // ---------- 4. ¡Gracias por tu compra! + factura ----------
    public function exito(Request $request, Pedido $pedido)
    {
        abort_unless($pedido->user_id === $request->user()->id, 403);

        if (! $pedido->codigo_recojo) {
            return redirect()->route('pedidos.historial');
        }

        $datos = FacturaController::datos($pedido);
        $qrRecojo = Qr::dataUri($pedido->codigo_recojo);

        return view('pedidos.exito', $datos + ['qrRecojo' => $qrRecojo]);
    }

    // ---------- "App del banco" de demostración (se abre al escanear el QR de prueba) ----------
    public function escanear(string $referencia)
    {
        $pago = Pago::where('referencia', $referencia)->firstOrFail();

        abort_unless($pago->metodo === 'demo', 404);

        $pago->refrescarVencimiento();

        return view('pagos.escanear', compact('pago'));
    }

    public function ejecutar(Request $request, string $referencia)
    {
        $pago = Pago::where('referencia', $referencia)->firstOrFail();

        abort_unless($pago->metodo === 'demo', 404);

        if ($request->input('accion') === 'rechazar') {
            $pago->fallar('Rechazaste el pago desde la app del banco (simulación).');
            $resultado = 'rechazado';
        } else {
            $resultado = $this->registrarRecibido($pago);
        }

        return redirect()->route('pago.escanear', $referencia)->with('resultado', $resultado);
    }

    // El dinero llegó: se marca y se envía el código al correo
    private function registrarRecibido(Pago $pago): string
    {
        $resultado = $pago->marcarRecibido();

        if ($resultado === 'recibido') {
            $pago->enviarCodigo();
        }

        return $resultado;
    }

    // Envía "¡Gracias por tu compra!" con el código de recojo y el resumen de la factura
    private function enviarComprobante(int $pedidoId): void
    {
        try {
            $pedido = Pedido::with('detalles.producto', 'factura', 'user')->find($pedidoId);

            if ($pedido) {
                Mail::to($pedido->user->email)->send(new PagoConfirmadoMail($pedido));
            }
        } catch (\Throwable $e) {
            report($e); // si el correo falla, la compra igual queda confirmada
        }
    }

    private function pedidoDelUsuario(Request $request, Pago $pago): Pedido
    {
        $pedido = $pago->pedido;

        abort_unless($pedido->user_id === $request->user()->id, 403);

        return $pedido;
    }

    // juan.perez@dominio -> ju*********@dominio
    private function enmascarar(string $correo): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $correo, 2), 2, '');

        return mb_substr($usuario, 0, 2) . str_repeat('*', max(1, mb_strlen($usuario) - 2)) . '@' . $dominio;
    }
}