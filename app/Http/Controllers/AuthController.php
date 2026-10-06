<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\CorreoInstitucional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // ---------- HU-01: Registro ----------
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $datos = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email', new CorreoInstitucional],
            'rol'      => ['required', Rule::in(['estudiante', 'docente'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'Escribe tu nombre.',
            'email.required'     => 'Escribe tu correo institucional.',
            'email.email'        => 'Escribe un correo válido.',
            'email.unique'       => 'Este correo ya está registrado.',
            'rol.required'       => 'Elige si eres estudiante o docente.',
            'password.required'  => 'Escribe una contraseña.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        User::create($datos);

        return redirect()
            ->route('login')
            ->with('status', 'Registro exitoso. Ya puedes iniciar sesión.');
    }

    // ---------- HU-02: Inicio de sesión ----------
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email'    => ['required', 'email', new CorreoInstitucional],
            'password' => ['required'],
        ], [
            'email.required'    => 'Escribe tu correo institucional.',
            'email.email'       => 'Escribe un correo válido.',
            'password.required' => 'Escribe tu contraseña.',
        ]);

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'El correo o la contraseña son incorrectos.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}