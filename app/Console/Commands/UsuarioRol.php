<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UsuarioRol extends Command
{
    protected $signature = 'usuario:rol {correo : Correo del usuario} {rol : estudiante, docente o encargado}';

    protected $description = 'Cambia el rol de un usuario (por ejemplo, para que pueda usar la caja)';

    public function handle(): int
    {
        $rol = $this->argument('rol');

        if (! in_array($rol, ['estudiante', 'docente', 'encargado'], true)) {
            $this->error('El rol debe ser: estudiante, docente o encargado.');

            return self::FAILURE;
        }

        $usuario = User::where('email', $this->argument('correo'))->first();

        if (! $usuario) {
            $this->error('No existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $usuario->update(['rol' => $rol]);

        $this->info('Listo: ' . $usuario->email . ' ahora es ' . $rol . '.');

        return self::SUCCESS;
    }
}