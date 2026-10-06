@extends('layouts.panel')

@section('titulo', 'Factura ' . $factura->numero_formateado)

@section('contenido')
    <div class="acciones no-imprimir" style="max-width:780px; margin:0 auto 14px;">
        <button type="button" class="btn" onclick="window.print()">🖨️ Imprimir / guardar PDF</button>
        <a class="btn gris" href="{{ route('pedidos.historial') }}">Volver a mis pedidos</a>
    </div>

    @include('facturas._documento')
@endsection