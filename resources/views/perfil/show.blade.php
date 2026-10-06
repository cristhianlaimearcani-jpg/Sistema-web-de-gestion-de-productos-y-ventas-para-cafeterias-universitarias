@extends('layouts.panel')

@section('titulo', 'Mi perfil')

@section('contenido')
    <h1>Mi perfil</h1>

    <div class="columnas">
        {{-- Todos los datos del usuario --}}
        <section class="tarjeta">
            <h2>Mis datos</h2>

            <div style="margin-bottom: 14px;">
                @include('partials.avatar', ['user' => $user, 'tam' => 96])
            </div>

            <p class="dato"><strong>Nombre:</strong> {{ $user->name }}</p>
            <p class="dato"><strong>Correo institucional:</strong> {{ $user->email }}</p>
            <p class="dato"><strong>Tipo de usuario:</strong> {{ ucfirst($user->rol) }}</p>
            <p class="dato"><strong>Teléfono:</strong> {{ $user->telefono ?? 'No registrado' }}</p>
            <p class="dato"><strong>Miembro desde:</strong> {{ $user->created_at->format('d/m/Y') }}</p>
            <p class="dato"><strong>Pedidos realizados:</strong> {{ $totalPedidos }}</p>

            <a class="btn" style="display:inline-block; text-decoration:none;"
               href="{{ route('pedidos.historial') }}">Ver mis pedidos</a>
        </section>

        {{-- Edición --}}
        <section class="tarjeta">
            <h2>Editar perfil</h2>

            <form method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <label for="name">Nombre</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror

                <label for="email">Correo institucional</label>
                <input type="email" id="email" value="{{ $user->email }}" disabled>
                <span class="ayuda">El correo no se puede modificar.</span>

                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $user->telefono) }}">
                @error('telefono') <span class="error">{{ $message }}</span> @enderror

                <label for="foto">Foto de perfil</label>
                <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
                <span class="ayuda">JPG, PNG o WEBP. Máximo 2 MB.</span>
                @error('foto') <span class="error">{{ $message }}</span> @enderror

                <br>
                <button type="submit" class="btn">Guardar cambios</button>
            </form>

            @if ($user->foto)
                <form method="POST" action="{{ route('perfil.foto.eliminar') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn rojo">Quitar foto</button>
                </form>
            @endif
        </section>
    </div>
@endsection