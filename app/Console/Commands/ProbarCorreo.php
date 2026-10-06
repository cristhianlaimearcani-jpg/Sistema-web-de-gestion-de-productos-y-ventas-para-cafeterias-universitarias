<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProbarCorreo extends Command
{
    protected $signature = 'correo:probar {correo : Dirección que recibirá el mensaje de prueba}';

    protected $description = 'Envía un correo de prueba para comprobar la configuración de CafeQR';

    public function handle(): int
    {
        $destino = $this->argument('correo');

        $this->line('Enviando con: ' . config('mail.default') . ' / ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'));

        try {
            Mail::raw(
                "Hola, si estás leyendo esto el correo de CafeQR funciona correctamente.",
                fn ($mensaje) => $mensaje->to($destino)->subject('Prueba de correo de CafeQR')
            );
        } catch (\Throwable $e) {
            $this->error('No se pudo enviar: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Listo. Revisa la bandeja de entrada (y la carpeta de spam) de ' . $destino);

        return self::SUCCESS;
    }
}