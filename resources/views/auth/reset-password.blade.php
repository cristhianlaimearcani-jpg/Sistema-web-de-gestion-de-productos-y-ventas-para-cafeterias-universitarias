@extends('layouts.guest')

@section('titulo', 'Nueva contraseña')
@section('ancho', 'ancha')

@section('contenido')
    <h1>Crear nueva contraseña</h1>
    <p class="subtitulo">Elige una contraseña segura para tu cuenta.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="campo {{ $errors->has('email') ? 'con-error' : '' }}">
            <label for="email">Correo</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required>
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="fila-2">
            <div class="campo {{ $errors->has('password') ? 'con-error' : '' }}">
                <label for="password">Nueva contraseña</label>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required autofocus>
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="campo">
                <label for="password_confirmation">Repite la contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
            </div>
        </div>
        <p class="requisitos">Mínimo 8 caracteres, con al menos una letra y un número.</p>

        <button type="submit" class="enviar" data-procesando="Guardando…">Guardar contraseña</button>
    </form>

    <p class="pie"><a href="{{ route('login') }}" data-transicion="atras">← Volver a iniciar sesión</a></p>
@endsection
