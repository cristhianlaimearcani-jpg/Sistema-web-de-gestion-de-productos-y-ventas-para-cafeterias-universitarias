<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PerfilController extends Controller
{
    // ---------- HU-03: Editar perfil ----------
    public function show(Request $request)
    {
        $user = $request->user();

        return view('perfil.show', [
            'user'         => $user,
            'totalPedidos' => $user->pedidos()->count(),
        ]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'foto'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'El nombre no puede quedar vacío.',
            'foto.image'    => 'El archivo debe ser una imagen.',
            'foto.mimes'    => 'Formatos permitidos: JPG, PNG o WEBP.',
            'foto.max'      => 'La foto no puede pesar más de 2 MB.',
            'foto.uploaded' => 'No se pudo subir la foto. Prueba con una imagen de menos de 2 MB.',
        ]);

        $user = $request->user();

        // El correo NO se modifica (HU-03).
        $user->name     = $datos['name'];
        $user->telefono = $datos['telefono'] ?? null;

        if ($request->hasFile('foto')) {
            $this->borrarFoto($user->foto);

            $archivo = $request->file('foto');
            $nombre  = Str::uuid() . '.' . $archivo->extension();
            $archivo->move(public_path('fotos'), $nombre);

            $user->foto = 'fotos/' . $nombre;
        }

        $user->save();

        return back()->with('status', 'Perfil actualizado correctamente.');
    }

    public function eliminarFoto(Request $request)
    {
        $user = $request->user();

        $this->borrarFoto($user->foto);
        $user->foto = null;
        $user->save();

        return back()->with('status', 'Foto eliminada.');
    }

    private function borrarFoto(?string $ruta): void
    {
        if ($ruta && file_exists(public_path($ruta))) {
            @unlink(public_path($ruta));
        }
    }
}