<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;

// HU-14: el estudiante sigue el estado de su pedido
class SeguimientoController extends Controller
{
    public function show(Request $request, Pedido $pedido)
    {
        abort_unless($pedido->user_id === $request->user()->id, 403);

        $pedido->load('detalles.producto');

        return view('pedidos.seguimiento', compact('pedido'));
    }

    // La pantalla consulta esto cada pocos segundos y se actualiza sola
    public function estado(Request $request, Pedido $pedido)
    {
        abort_unless($pedido->user_id === $request->user()->id, 403);

        return response()->json([
            'estado' => $pedido->estado,
            'texto'  => $pedido->estado_texto,
        ]);
    }
}