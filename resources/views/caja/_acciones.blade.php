@foreach ($transiciones[$pedido->estado] ?? [] as $siguiente)
    <form method="POST" action="{{ route('caja.entregas.estado', $pedido) }}">
        @csrf
        <input type="hidden" name="estado" value="{{ $siguiente }}">
        <button type="submit" class="btn chico {{ $siguiente === 'entregado' ? '' : 'gris' }}">{{ $etiquetas[$siguiente] }}</button>
    </form>
@endforeach