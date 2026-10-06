<?php

namespace App\Http\Controllers;

use App\Mail\PedidoListoMail;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

// Pantalla de la cafetería: validar el código de recojo y entregar los pedidos
class EntregaController extends Controller
{
    // Estados a los que se puede pasar desde cada estado
    private const TRANSICIONES = [
        'pagado'         => ['en_preparacion', 'listo', 'entregado'],
        'en_preparacion' => ['listo', 'entregado'],
        'listo'          => ['entregado'],
    ];

    private const ETIQUETAS = [
        'en_preparacion' => '👨‍🍳 En preparación',
        'listo'          => '🔔 Listo para recoger',
        'entregado'      => '✅ Entregado',
    ];

    public function index(Request $request)
    {
        $this->autorizar($request);

        $codigo = strtoupper(trim((string) $request->query('codigo', '')));

        $encontrado = $codigo !== ''
            ? Pedido::with('user', 'detalles.producto')->where('codigo_recojo', $codigo)->first()
            : null;

        $activos = Pedido::with('user', 'detalles.producto')
            ->whereIn('estado', ['pagado', 'en_preparacion', 'listo'])
            ->orderBy('pagado_en')
            ->get();

        $entregadosHoy = Pedido::with('user')
            ->where('estado', 'entregado')
            ->whereDate('updated_at', today())
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('caja.entregas', [
            'codigo'        => $codigo,
            'encontrado'    => $encontrado,
            'activos'       => $activos,
            'entregadosHoy' => $entregadosHoy,
            'transiciones'  => self::TRANSICIONES,
            'etiquetas'     => self::ETIQUETAS,
        ]);
    }

    public function cambiar(Request $request, Pedido $pedido)
    {
        $this->autorizar($request);

        $nuevo = $request->validate([
            'estado' => ['required', 'in:en_preparacion,listo,entregado'],
        ])['estado'];

        $permitidos = self::TRANSICIONES[$pedido->estado] ?? [];

        if (! in_array($nuevo, $permitidos, true)) {
            return back()->with('error', 'No se puede pasar el pedido #' . $pedido->id . ' de "' . $pedido->estado_texto . '" a ese estado.');
        }

        $pedido->update(['estado' => $nuevo]);

        $mensaje = 'Pedido #' . $pedido->id . ': ' . self::ETIQUETAS[$nuevo] . '.';

        // HU-13: al estar listo, se avisa al estudiante por correo
        if ($nuevo === 'listo') {
            $mensaje .= $this->avisarListo($pedido)
                ? ' Avisamos al estudiante por correo.'
                : ' (No se pudo enviar el correo, pero el estudiante lo verá en su pantalla.)';
        }

        return back()->with('status', $mensaje);
    }

    private function avisarListo(Pedido $pedido): bool
    {
        try {
            $pedido->load('user', 'detalles.producto');

            Mail::to($pedido->user->email)->send(new PedidoListoMail($pedido));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function autorizar(Request $request): void
    {
        abort_unless($request->user()->rol === 'encargado', 403);
    }
}