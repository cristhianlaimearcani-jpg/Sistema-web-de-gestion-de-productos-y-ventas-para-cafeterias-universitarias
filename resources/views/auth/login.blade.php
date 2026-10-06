@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <h1>Iniciar sesión</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Correo institucional</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email') <span class="error">{{ $message }}</span> @enderror

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        @error('password') <span class="error">{{ $message }}</span> @enderror

        <label>
            <input type="checkbox" name="remember"> Recordarme
        </label>

        <button type="submit">Entrar</button>
    </form>

    <p class="pie"><a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a></p>
    <p class="pie">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
@endsection