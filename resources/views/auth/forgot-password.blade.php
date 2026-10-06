@extends('layouts.app')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
    <h1>Recuperar contraseña</h1>
    <p>Escribe tu correo institucional y te enviaremos un enlace para crear una contraseña nueva.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="email">Correo institucional</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email') <span class="error">{{ $message }}</span> @enderror

        <button type="submit">Enviar enlace</button>
    </form>

    <p class="pie"><a href="{{ route('login') }}">Volver al inicio de sesión</a></p>
@endsection