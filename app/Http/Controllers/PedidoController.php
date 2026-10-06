<?php

namespace App\Http\Controllers;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    // ---------- HU-15: Historial de pedidos ----------
    public function historial(Request $request)
    {
        $pedidos = $request->user()
            ->pedidos()
            ->with('detalles.producto', 'factura')
            ->latest()
            ->get();

        return view('pedidos.historial', compact('pedidos'));
    }

    // ---------- HU-09: Seleccionar productos y generar pedido ----------

    // Ver el pedido que se está armando
    public function actual(Request $request)
    {
        $pedido = $request->user()->pedidoPendiente();

        return view('pedidos.actual', compact('pedido'));
    }

    // Agregar un producto (uno o varios) al pedido
    public function agregar(Request $request, Producto $producto)
    {
        // HU-08: los productos agotados no se pueden seleccionar
        if (! $producto->disponible) {
            return back()->with('error', 'Ese producto está agotado y no se puede agregar.');
        }

        $datos = $request->validate([
            'cantidad' => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'cantidad.integer' => 'La cantidad debe ser un número.',
            'cantidad.min'     => 'La cantidad mínima es 1.',
            'cantidad.max'     => 'Puedes agregar hasta 10 unidades a la vez.',
        ]);

        $cantidad = (int) ($datos['cantidad'] ?? 1);

        $pedido = $request->user()->pedidoPendiente();

        if ($pedido && $pedido->tienePagoEnProceso()) {
            return $this->bloqueado();
        }

        $pedido ??= Pedido::create([
            'user_id' => $request->user()->id,
            'estado'  => 'pendiente',
            'total'   => 0,
        ]);

        $detalle = $pedido->detalles()->where('producto_id', $producto->id)->first();

        if ($detalle) {
            $detalle->cantidad = min(20, $detalle->cantidad + $cantidad);
            $detalle->save();
        } else {
            $pedido->detalles()->create([
                'producto_id'     => $producto->id,
                'cantidad'        => $cantidad,
                'precio_unitario' => $producto->precio,
            ]);
        }

        $pedido->recalcularTotal();

        return back()->with('status', $producto->nombre . ' se agregó a tu pedido.');
    }

    // Cambiar la cantidad de un producto del pedido
    public function actualizar(Request $request, DetallePedido $detalle)
    {
        $pedido = $this->pedidoModificable($request, $detalle);

        if ($pedido->tienePagoEnProceso()) {
            return $this->bloqueado();
        }

        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'cantidad.required' => 'Escribe una cantidad.',
            'cantidad.integer'  => 'La cantidad debe ser un número.',
            'cantidad.min'      => 'La cantidad mínima es 1.',
            'cantidad.max'      => 'La cantidad máxima es 20.',
        ]);

        $detalle->update(['cantidad' => $datos['cantidad']]);
        $pedido->recalcularTotal();

        return back()->with('status', 'Cantidad actualizada.');
    }

    // Quitar un producto del pedido
    public function quitar(Request $request, DetallePedido $detalle)
    {
        $pedido = $this->pedidoModificable($request, $detalle);

        if ($pedido->tienePagoEnProceso()) {
            return $this->bloqueado();
        }

        $detalle->delete();

        if ($pedido->detalles()->count() === 0) {
            $pedido->delete();

            return redirect()->route('dashboard')->with('status', 'Tu pedido quedó vacío.');
        }

        $pedido->recalcularTotal();

        return back()->with('status', 'Producto quitado del pedido.');
    }

    // Cancelar todo el pedido que se está armando
    public function vaciar(Request $request)
    {
        $pedido = $request->user()->pedidoPendiente();

        if ($pedido) {
            if ($pedido->tienePagoEnProceso()) {
                return $this->bloqueado();
            }

            $pedido->detalles()->delete();
            $pedido->delete();
        }

        return redirect()->route('dashboard')->with('status', 'Pedido cancelado.');
    }

    // Revisar y confirmar el pedido antes de pagar
    public function confirmar(Request $request)
    {
        $pedido = $request->user()->pedidoPendiente();

        if (! $pedido || $pedido->detalles->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'Primero agrega productos a tu pedido.');
        }

        // Si el pago ya está en proceso, se continúa en esa pantalla
        $activo = $pedido->pagoActivo();

        if ($activo && in_array($activo->estado, ['por_verificar', 'recibido'], true)) {
            return redirect()->route('pago.show', $activo);
        }

        $agotados = $pedido->detalles->filter(fn ($d) => ! $d->producto->disponible);

        if ($agotados->isNotEmpty()) {
            return redirect()->route('pedido.actual')->with(
                'error',
                'Estos productos se agotaron: ' . $agotados->pluck('producto.nombre')->join(', ') . '. Quítalos para continuar.'
            );
        }

        return view('pedidos.confirmar', compact('pedido'));
    }

    // Solo se puede modificar un pedido propio que todavía no fue pagado
    private function pedidoModificable(Request $request, DetallePedido $detalle): Pedido
    {
        $pedido = $detalle->pedido;

        abort_unless(
            $pedido->user_id === $request->user()->id && $pedido->estado === 'pendiente',
            403
        );

        return $pedido;
    }

    private function bloqueado()
    {
        return back()->with('error', 'Tu pago está en proceso: no puedes cambiar el pedido hasta que termine.');
    }
}