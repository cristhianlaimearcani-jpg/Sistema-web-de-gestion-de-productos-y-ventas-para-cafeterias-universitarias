<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Ticket;
use App\Services\Notificador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Soporte: el cliente cuenta un problema (pago, pedido, error...) y el personal le responde.
 * Cada consulta es una conversación con estado: Abierto → En revisión → Resuelto.
 */
class SoporteController extends Controller
{
    /* ----------------------------- Cliente ----------------------------- */

    public function index(Request $request): View
    {
        $usuario = $request->user();

        return view('soporte.index', [
            'tickets' => $usuario->tickets()->withCount('mensajes')->latest('updated_at')->get(),
            'pedidos' => $usuario->pedidos()->where('estado', '!=', Pedido::CARRITO)->latest('id')->limit(15)->get(),
            'pedidoElegido' => (int) $request->query('pedido') ?: null,
            'tipoElegido' => array_key_exists((string) $request->query('tipo'), Ticket::TIPOS) ? $request->query('tipo') : null,
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(Ticket::TIPOS))],
            'pedido_id' => ['nullable', 'integer', Rule::exists('pedidos', 'id')->where('user_id', $usuario->id)],
            'asunto' => ['required', 'string', 'min:5', 'max:120'],
            'mensaje' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'tipo.required' => 'Elige de qué se trata tu consulta.',
            'tipo.in' => 'El tipo de consulta no es válido.',
            'pedido_id.exists' => 'Ese pedido no es tuyo.',
            'asunto.required' => 'Escribe un asunto corto.',
            'asunto.min' => 'El asunto debe tener al menos 5 caracteres.',
            'asunto.max' => 'El asunto no puede pasar de 120 caracteres.',
            'mensaje.required' => 'Cuéntanos qué pasó.',
            'mensaje.min' => 'Cuéntanos un poco más (al menos 10 caracteres).',
            'mensaje.max' => 'El mensaje no puede pasar de 2000 caracteres.',
        ]);

        $ticket = $usuario->tickets()->create([
            'tipo' => $datos['tipo'],
            'pedido_id' => $datos['pedido_id'] ?? null,
            'asunto' => trim($datos['asunto']),
            'estado' => Ticket::ABIERTO,
        ]);
        $ticket->mensajes()->create(['user_id' => $usuario->id, 'es_personal' => false, 'mensaje' => trim($datos['mensaje'])]);

        return redirect()->route('soporte.show', $ticket)
            ->with('exito', 'Recibimos tu consulta #'.$ticket->id.'. Te responderemos aquí y por correo.');
    }

    public function mostrar(Request $request, Ticket $ticket): View
    {
        $this->puedeVer($request, $ticket);
        $ticket->load(['mensajes.user', 'user', 'pedido.detalles']);

        return view('soporte.show', ['ticket' => $ticket, 'personal' => $request->user()->esPersonal()]);
    }

    /** Responder: cliente o personal. El personal también puede cambiar el estado. */
    public function responder(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->puedeVer($request, $ticket);
        $usuario = $request->user();
        $esPersonal = $usuario->esPersonal();

        $datos = $request->validate([
            'mensaje' => [$esPersonal ? 'nullable' : 'required', 'string', 'max:2000'],
            'estado' => ['nullable', Rule::in(array_keys(Ticket::ESTADOS))],
        ], [
            'mensaje.required' => 'Escribe tu mensaje.',
            'mensaje.max' => 'El mensaje no puede pasar de 2000 caracteres.',
        ]);

        $mensaje = trim((string) ($datos['mensaje'] ?? ''));
        if ($mensaje === '' && ! $esPersonal) {
            return back()->withErrors(['mensaje' => 'Escribe tu mensaje.']);
        }

        if ($mensaje !== '') {
            $ticket->mensajes()->create(['user_id' => $usuario->id, 'es_personal' => $esPersonal, 'mensaje' => $mensaje]);
        }

        if ($esPersonal) {
            $estado = $datos['estado'] ?? ($mensaje !== '' ? Ticket::EN_PROCESO : $ticket->estado);
            if ($mensaje === '' && $estado === $ticket->estado) {
                return back()->withErrors(['mensaje' => 'Escribe una respuesta o cambia el estado.']);
            }
            $ticket->update(['estado' => $estado, 'resuelto_en' => $estado === Ticket::RESUELTO ? now() : null]);
            $ticket->touch();

            $aviso = 'Consulta #'.$ticket->id.' actualizada ('.$ticket->estado_texto.').';
            if ($mensaje !== '') {
                $aviso .= Notificador::soporteRespuesta($ticket->fresh('user'), $mensaje)
                    ? ' Le avisamos al cliente por correo.'
                    : ' No se pudo enviar el correo al cliente, pero verá la respuesta aquí.';
            }

            return back()->with('exito', $aviso);
        }

        // Si el cliente escribe en una consulta resuelta, se vuelve a abrir.
        $ticket->update(['estado' => $ticket->estado === Ticket::RESUELTO ? Ticket::ABIERTO : $ticket->estado, 'resuelto_en' => null]);
        $ticket->touch();

        return back()->with('exito', 'Mensaje enviado.');
    }

    /** El cliente marca su consulta como resuelta. */
    public function resolver(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->puedeVer($request, $ticket);
        $ticket->update(['estado' => Ticket::RESUELTO, 'resuelto_en' => now()]);

        return back()->with('exito', '¡Qué bueno! Marcamos tu consulta como resuelta.');
    }

    /* ----------------------------- Personal ----------------------------- */

    public function gestion(Request $request): View
    {
        $estado = $request->query('estado', 'pendientes');
        $tipo = $request->query('tipo');

        $tickets = Ticket::query()
            ->with(['user', 'pedido'])
            ->withCount('mensajes')
            ->when($estado === 'pendientes', fn ($q) => $q->whereIn('estado', [Ticket::ABIERTO, Ticket::EN_PROCESO]))
            ->when(array_key_exists((string) $estado, Ticket::ESTADOS), fn ($q) => $q->where('estado', $estado))
            ->when(array_key_exists((string) $tipo, Ticket::TIPOS), fn ($q) => $q->where('tipo', $tipo))
            ->orderByRaw("CASE estado WHEN 'abierto' THEN 0 WHEN 'en_proceso' THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->paginate(15)->withQueryString();

        $cuentas = Ticket::selectRaw('estado, count(*) as cantidad')->groupBy('estado')->pluck('cantidad', 'estado');

        return view('soporte.gestion', [
            'tickets' => $tickets,
            'estado' => $estado,
            'tipo' => $tipo,
            'cuentas' => $cuentas,
        ]);
    }

    private function puedeVer(Request $request, Ticket $ticket): void
    {
        $usuario = $request->user();
        abort_unless($usuario->esPersonal() || $ticket->user_id === $usuario->id, 403);
    }
}
