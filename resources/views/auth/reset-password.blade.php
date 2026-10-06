@extends('layouts.app')

@section('titulo', 'Nueva contraseña')

@section('contenido')
    <h1>Nueva contraseña</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Correo institucional</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required>
        @error('email') <span class="error">{{ $message }}</span> @enderror

        <label for="password">Nueva contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="password" name="password" required>
        @error('password') <span class="error">{{ $message }}</span> @enderror

        <label for="password_confirmation">Repetir contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>

        <button type="submit">Cambiar contraseña</button>
    </form>
@endsection