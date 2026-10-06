<?php

namespace App\Models;

use App\Mail\CodigoVerificacionMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class Verificacion extends Model
{
    protected $table = 'verificaciones';

    public const MAX_INTENTOS = 5;
    public const MINUTOS = 10;

    protected $fillable = ['user_id', 'pago_id', 'codigo_hash', 'intentos', 'expira_en', 'usado_en'];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'usado_en'  => 'datetime',
        ];
    }

    // Crea un código nuevo de 6 dígitos y lo envía por correo al estudiante.
    // Si el correo no se puede enviar, lanza la excepción y no deja nada guardado.
    public static function enviarA(Pago $pago): self
    {
        $pedido = $pago->pedido;
        $user = $pedido->user;

        static::where('pago_id', $pago->id)->whereNull('usado_en')->delete();

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verificacion = static::create([
            'user_id'     => $user->id,
            'pago_id'     => $pago->id,
            'codigo_hash' => Hash::make($codigo),
            'expira_en'   => now()->addMinutes(self::MINUTOS),
        ]);

        try {
            Mail::to($user->email)->send(new CodigoVerificacionMail(
                $user->name,
                $codigo,
                self::MINUTOS,
                $pedido->id,
                number_format((float) $pago->monto, 2),
            ));
        } catch (\Throwable $e) {
            $verificacion->delete();

            throw $e;
        }

        return $verificacion;
    }

    // Devuelve: ok | incorrecto | expirado | bloqueado
    public function comprobar(string $codigo): string
    {
        if ($this->usado_en || $this->expira_en->isPast()) {
            return 'expirado';
        }

        if ($this->intentos >= self::MAX_INTENTOS) {
            return 'bloqueado';
        }

        if (! Hash::check($codigo, $this->codigo_hash)) {
            $this->increment('intentos');

            return $this->intentos >= self::MAX_INTENTOS ? 'bloqueado' : 'incorrecto';
        }

        $this->update(['usado_en' => now()]);

        return 'ok';
    }

    public function intentosRestantes(): int
    {
        return max(0, self::MAX_INTENTOS - $this->intentos);
    }
}