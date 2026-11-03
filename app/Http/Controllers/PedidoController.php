<?php

namespace App\Http\Controllers;

use App\Models\Cupon;
use App\Models\DetallePedido;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Support\Qr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PedidoController extends Controller
{
    private const MAX_POR_PRODUCTO = 20;

    /** Mi pedido: el carrito, o el pedido que espera pago. */
    public function actual(Request $request): View
    {
        $pedido = $request->user()->pedidoEnCurso();
        $pedido?->load('detalles.producto');

        return view('pedidos.actual', ['pedido' => $pedido]);
    }

    public function agregar(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validate([
            'cantidad' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_POR_PRODUCTO],
        ], [
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad mínima es 1.',
            'cantidad.max' => 'Puedes pedir hasta '.self::MAX_POR_PRODUCTO.' unidades de cada producto.',
        ]);

        if ($producto->agotado) {
            return back()->with('error', $producto->nombre.' está agotado por ahora.');
        }

        $usuario = $request->user();
        $pedido = $usuario->pedidos()->where('estado', Pedido::CARRITO)->latest('id')->first();

        if (! $pedido) {
            if ($usuario->pedidos()->where('estado', Pedido::PENDIENTE_PAGO)->exists()) {
                return redirect()->route('pedido.actual')
                    ->with('error', 'Tienes un pedido esperando pago. Págalo, modifícalo o cancélalo antes de agregar más productos.');
            }
            $pedido = Pedido::create(['user_id' => $usuario->id, 'estado' => Pedido::CARRITO, 'total' => 0]);
        }

        $cantidad = (int) ($datos['cantidad'] ?? 1);
        $detalle = $pedido->detalles()->where('producto_id', $producto->id)->first();
        $total = ($detalle?->cantidad ?? 0) + $cantidad;

        if ($total > $producto->stock) {
            $yaTienes = $detalle?->cantidad ?? 0;

            return back()->with('error', 'Solo quedan '.$producto->stock.' unidades de '.$producto->nombre.'.'
                .($yaTienes ? ' Ya tienes '.$yaTienes.' en tu pedido.' : ''));
        }

        if ($detalle) {
            $detalle->update([
                'cantidad' => min(self::MAX_POR_PRODUCTO, $detalle->cantidad + $cantidad),
                'nombre' => $producto->nombre,
                'precio_unitario' => $producto->precio,
            ]);
        } else {
            $pedido->detalles()->create([
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio_unitario' => $producto->precio,
                'cantidad' => $cantidad,
            ]);
        }

        $pedido->recalcularTotal();

        return back()->with('exito', $producto->nombre.' se agregó a tu pedido.');
    }

    public function cambiar(Request $request, DetallePedido $detalle): RedirectResponse
    {
        $pedido = $this->pedidoEditable($request, $detalle);

        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:'.self::MAX_POR_PRODUCTO],
        ], [
            'cantidad.required' => 'Escribe la cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad mínima es 1.',
            'cantidad.max' => 'Puedes pedir hasta '.self::MAX_POR_PRODUCTO.' unidades de cada producto.',
        ]);

        $stock = (int) ($detalle->producto?->stock ?? 0);
        if ((int) $datos['cantidad'] > $stock) {
            return back()->with('error', 'Solo quedan '.$stock.' unidades de '.$detalle->nombre.'.');
        }

        $detalle->update(['cantidad' => (int) $datos['cantidad']]);
        $pedido->recalcularTotal();

        return back()->with('exito', 'Actualizamos la cantidad.');
    }

    public function quitar(Request $request, DetallePedido $detalle): RedirectResponse
    {
        $pedido = $this->pedidoEditable($request, $detalle);
        $detalle->delete();
        $pedido->recalcularTotal();

        if ($pedido->detalles()->count() === 0) {
            $pedido->delete();

            return redirect()->route('dashboard')->with('exito', 'Quitaste el producto. Tu pedido quedó vacío.');
        }

        return back()->with('exito', 'Quitaste el producto de tu pedido.');
    }

    /** Pantalla "Último paso": datos de facturación y forma de pago. */
    public function confirmar(Request $request): View|RedirectResponse
    {
        $pedido = $request->user()->pedidos()->where('estado', Pedido::CARRITO)->latest('id')->first();

        if (! $pedido || $pedido->detalles()->count() === 0) {
            return redirect()->route('pedido.actual')->with('error', 'Primero agrega productos a tu pedido.');
        }

        if ($problema = $this->revisarDisponibilidad($pedido)) {
            return redirect()->route('pedido.actual')->with('error', $problema);
        }

        $pedido->load('detalles');

        return view('pedidos.confirmar', [
            'pedido' => $pedido,
            'usuario' => $request->user(),
            // Clave única de este intento de compra: si el formulario llega dos veces, no se crea otro pedido.
            'claveIntento' => old('clave_intento') ?: (string) Str::uuid(),
            'repetido' => $pedido->igualReciente()?->load('detalles'),
        ]);
    }

    /** Aplica un cupón de descuento al pedido que se está armando. */
    public function aplicarCupon(Request $request): RedirectResponse
    {
        $datos = $request->validate(['cupon' => ['required', 'string', 'max:20']], [
            'cupon.required' => 'Escribe el código del cupón.',
            'cupon.max' => 'El código del cupón es muy largo.',
        ]);

        $pedido = $request->user()->pedidos()->where('estado', Pedido::CARRITO)->latest('id')->first();
        if (! $pedido || $pedido->detalles()->count() === 0) {
            return redirect()->route('pedido.actual')->with('error', 'Primero agrega productos a tu pedido.');
        }

        $cupon = Cupon::buscar($datos['cupon']);
        if (! $cupon) {
            return back()->with('error', 'Ese cupón no existe o ya no está activo.');
        }

        if ($pedido->subtotalProductos() <= (float) $cupon->monto) {
            return back()->with('error', 'El cupón '.$cupon->codigo.' descuenta Bs '.number_format($cupon->monto, 2).' y se usa en pedidos mayores a ese monto.');
        }

        $pedido->update(['cupon' => $cupon->codigo]);
        $pedido->recalcularTotal();

        return back()->with('exito', 'Cupón '.$cupon->codigo.' aplicado: −Bs '.number_format($cupon->monto, 2).'.');
    }

    public function quitarCupon(Request $request): RedirectResponse
    {
        $pedido = $request->user()->pedidos()->where('estado', Pedido::CARRITO)->latest('id')->first();
        if ($pedido) {
            $pedido->update(['cupon' => null]);
            $pedido->recalcularTotal();
        }

        return back()->with('exito', 'Quitamos el cupón.');
    }

    /** Guarda los datos, deja el pedido pendiente de pago y abre la pantalla de pago elegida. */
    public function pagar(Request $request): RedirectResponse
    {
        $request->merge([
            'razon_social' => preg_replace('/\s+/u', ' ', trim((string) $request->input('razon_social'))) ?: null,
            'nit_ci' => preg_replace('/\s+/', '', (string) $request->input('nit_ci')) ?: null,
        ]);

        $datos = $request->validate([
            'metodo' => ['required', 'in:'.Pago::METODO_QR.','.Pago::METODO_EFECTIVO],
            'razon_social' => ['nullable', 'string', 'max:80', "regex:/^[\\pL\\d\\s.,&'()-]+$/u"],
            'nit_ci' => ['nullable', 'regex:/^\d{5,13}$/'],
        ], [
            'metodo.required' => 'Elige cómo quieres pagar.',
            'metodo.in' => 'La forma de pago elegida no es válida.',
            'razon_social.max' => 'El nombre para la factura es muy largo (máximo 80).',
            'razon_social.regex' => 'El nombre para la factura tiene caracteres no permitidos.',
            'nit_ci.regex' => 'El NIT o CI debe tener solo números (entre 5 y 13 dígitos).',
        ]);

        $usuario = $request->user();
        $clave = substr(preg_replace('/[^A-Za-z0-9-]/', '', (string) $request->input('clave_intento')), 0, 64);

        // Protección contra pedidos duplicados: si este mismo intento ya se registró, se devuelve ese pedido.
        if ($clave !== '' && ($existente = $usuario->pedidos()->where('clave_intento', $clave)->first())) {
            return $this->irAlPedido($existente, 'Tu pedido #'.$existente->id.' ya estaba registrado. No se creó otro.');
        }

        $pedido = $usuario->pedidos()->where('estado', Pedido::CARRITO)->latest('id')->first();

        if (! $pedido || $pedido->detalles()->count() === 0) {
            $pendiente = $usuario->pedidos()->where('estado', Pedido::PENDIENTE_PAGO)->latest('id')->first();
            if ($pendiente) {
                return $this->irAlPedido($pendiente, 'Tu pedido #'.$pendiente->id.' ya estaba registrado. No se creó otro.');
            }

            return redirect()->route('pedido.actual')->with('error', 'Tu pedido está vacío.');
        }

        if ($problema = $this->revisarDisponibilidad($pedido)) {
            return redirect()->route('pedido.actual')->with('error', $problema);
        }

        // Aviso de compra repetida: el cliente decide si ve el pedido anterior o compra otro igual.
        if (! $request->boolean('repetir') && ($igual = $pedido->igualReciente())) {
            return redirect()->route('pedido.confirmar')->withInput()
                ->with('repetido_aviso', 'Ya tienes un pedido igual (#'.$igual->id.'). ¿Deseas ver ese pedido o comprar otro?');
        }

        if ($problema = $pedido->reservarStock()) {
            return redirect()->route('pedido.actual')->with('error', $problema);
        }

        try {
            $pago = DB::transaction(function () use ($pedido, $datos, $clave) {
                $pedido->update([
                    'estado' => Pedido::PENDIENTE_PAGO,
                    'razon_social' => $datos['razon_social'] ?? null,
                    'nit_ci' => $datos['nit_ci'] ?? null,
                    'clave_intento' => $clave !== '' ? $clave : (string) Str::uuid(),
                ]);

                return Pago::iniciarPara($pedido, $datos['metodo']);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Llegaron dos envíos al mismo tiempo: el otro ya registró el pedido.
            $pedido->devolverStock();
            $existente = $usuario->pedidos()->where('clave_intento', $clave)->first();

            return $existente
                ? $this->irAlPedido($existente, 'Tu pedido #'.$existente->id.' ya estaba registrado. No se creó otro.')
                : redirect()->route('pedido.actual');
        }

        return redirect()->route('pago.show', $pago)
            ->with('exito', 'Pedido #'.$pedido->id.' registrado. Ahora completa el pago.');
    }

    /** Volver a la pantalla de pago de un pedido que ya estaba pendiente. */
    public function continuar(Request $request): RedirectResponse
    {
        $pedidos = $request->user()->pedidos()->where('estado', Pedido::PENDIENTE_PAGO);
        $pedido = $request->query('pedido')
            ? (clone $pedidos)->whereKey((int) $request->query('pedido'))->first()
            : null;
        $pedido ??= $pedidos->latest('id')->first();

        if (! $pedido) {
            return redirect()->route('pedidos.historial')->with('error', 'Ese pedido ya no está pendiente de pago.');
        }

        $pago = $pedido->pagoActivo();
        if (! $pago || $pago->estaVencido()) {
            $metodo = $pedido->pagos()->latest('id')->value('metodo') ?? Pago::METODO_QR;
            $pago = Pago::iniciarPara($pedido, $metodo);
        }

        return redirect()->route('pago.show', $pago);
    }

    /** Pedido pendiente de pago que el cliente quiere cambiar: vuelve a ser carrito. */
    public function modificar(Request $request): RedirectResponse
    {
        $pedido = $request->user()->pedidos()->where('estado', Pedido::PENDIENTE_PAGO)->latest('id')->first();

        if ($pedido) {
            if ($pedido->pagos()->whereIn('estado', ['recibido', 'completado'])->exists()) {
                return redirect()->route('pedido.continuar')
                    ->with('error', 'Ya recibimos tu pago, así que este pedido ya no se puede modificar.');
            }
            $pedido->pagos()->whereIn('estado', Pago::ABIERTOS)->update(['estado' => 'cancelado']);
            $pedido->devolverStock();
            // Nueva clave: al volver a pagar será otro intento de compra.
            $pedido->update(['estado' => Pedido::CARRITO, 'clave_intento' => null]);
        }

        return redirect()->route('pedido.actual')->with('exito', 'Puedes modificar tu pedido.');
    }

    public function historial(Request $request): View
    {
        $grupo = $request->query('ver');
        $grupo = array_key_exists((string) $grupo, Pedido::GRUPOS) ? $grupo : null;

        $base = $request->user()->pedidos()->where('estado', '!=', Pedido::CARRITO);
        $porEstado = (clone $base)->selectRaw('estado, count(*) as cantidad')->groupBy('estado')->pluck('cantidad', 'estado');
        $cuentas = ['todos' => (int) $porEstado->sum()];
        foreach (Pedido::GRUPOS as $clave => $g) {
            $cuentas[$clave] = (int) collect($g['estados'])->sum(fn ($e) => $porEstado[$e] ?? 0);
        }

        $pedidos = (clone $base)
            ->when($grupo, fn ($q) => $q->whereIn('estado', Pedido::GRUPOS[$grupo]['estados']))
            ->with(['detalles', 'factura'])
            ->latest('id')
            ->paginate(8)->withQueryString();

        return view('pedidos.historial', ['pedidos' => $pedidos, 'grupo' => $grupo, 'cuentas' => $cuentas]);
    }

    /** "¡Gracias por tu compra!" con el código de recojo. */
    public function exito(Request $request, Pedido $pedido): View|RedirectResponse
    {
        abort_unless($pedido->user_id === $request->user()->id, 403);

        if ($pedido->estado === Pedido::CARRITO) {
            return redirect()->route('pedido.actual');
        }
        if ($pedido->estado === Pedido::PENDIENTE_PAGO) {
            return redirect()->route('pedido.continuar', ['pedido' => $pedido->id]);
        }
        if ($pedido->estado === Pedido::CANCELADO) {
            return redirect()->route('pedidos.historial')->with('error', 'Ese pedido fue cancelado.');
        }

        $pedido->load(['detalles', 'factura', 'user']);

        return view('pedidos.exito', [
            'pedido' => $pedido,
            'qrRecojo' => Qr::dataUri('CAFEQR|'.$pedido->codigo),
        ] + ($pedido->factura ? FacturaController::datos($pedido) : []));
    }

    /** Lleva al cliente a la pantalla que corresponde según el estado de su pedido. */
    private function irAlPedido(Pedido $pedido, string $mensaje): RedirectResponse
    {
        if ($pedido->estado === Pedido::PENDIENTE_PAGO) {
            $pago = $pedido->pagoActivo();

            return ($pago ? redirect()->route('pago.show', $pago) : redirect()->route('pedido.continuar', ['pedido' => $pedido->id]))->with('exito', $mensaje);
        }
        if ($pedido->estado === Pedido::CANCELADO) {
            return redirect()->route('pedidos.historial')->with('error', 'El pedido #'.$pedido->id.' fue cancelado.');
        }

        return redirect()->route('pedido.exito', $pedido)->with('exito', $mensaje);
    }

    private function pedidoEditable(Request $request, DetallePedido $detalle): Pedido
    {
        $pedido = $detalle->pedido;
        abort_unless($pedido->user_id === $request->user()->id && $pedido->estado === Pedido::CARRITO, 403);

        return $pedido;
    }

    /**
     * Devuelve un mensaje si algún producto ya no está disponible; si todo está bien,
     * actualiza los precios al valor actual del menú y devuelve null.
     */
    private function revisarDisponibilidad(Pedido $pedido): ?string
    {
        $agotados = [];

        $pocos = [];

        foreach ($pedido->detalles()->with('producto')->get() as $detalle) {
            if (! $detalle->producto || $detalle->producto->agotado) {
                $agotados[] = $detalle->nombre;
            } elseif ($detalle->cantidad > $detalle->producto->stock) {
                $pocos[] = $detalle->nombre.' (quedan '.$detalle->producto->stock.')';
            }
        }

        if ($agotados) {
            return 'Estos productos ya no están disponibles: '.implode(', ', $agotados).'. Quítalos de tu pedido para continuar.';
        }
        if ($pocos) {
            return 'No alcanza el stock de: '.implode(', ', $pocos).'. Cambia la cantidad para continuar.';
        }

        foreach ($pedido->detalles()->with('producto')->get() as $detalle) {
            if ((float) $detalle->precio_unitario !== (float) $detalle->producto->precio) {
                $detalle->update(['precio_unitario' => $detalle->producto->precio, 'nombre' => $detalle->producto->nombre]);
            }
        }
        $pedido->recalcularTotal();

        return null;
    }
}
