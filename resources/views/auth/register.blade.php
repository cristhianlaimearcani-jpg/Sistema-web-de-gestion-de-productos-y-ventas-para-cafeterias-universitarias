@extends('layouts.app')

@section('titulo', 'Registro')

@section('contenido')
    <h1>Crear cuenta</h1>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror

        <label for="email">Correo institucional</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        @error('email') <span class="error">{{ $message }}</span> @enderror

        <label for="rol">Soy</label>
        <select id="rol" name="rol" required>
            <option value="estudiante" @selected(old('rol') === 'estudiante')>Estudiante</option>
            <option value="docente" @selected(old('rol') === 'docente')>Docente</option>
        </select>
        @error('rol') <span class="error">{{ $message }}</span> @enderror

        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="password" name="password" required>
        @error('password') <span class="error">{{ $message }}</span> @enderror

        <label for="password_confirmation">Repetir contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>

        <button type="submit">Registrarme</button>
    </form>

    <p class="pie">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
@endsection