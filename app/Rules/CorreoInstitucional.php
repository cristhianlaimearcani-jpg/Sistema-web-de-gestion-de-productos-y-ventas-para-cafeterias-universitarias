<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CorreoInstitucional implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Se pueden permitir varios dominios separados por coma en el .env
        // Ej.: DOMINIO_INSTITUCIONAL=univalle.edu,gmail.com
        $dominios = array_values(array_filter(array_map(
            'trim',
            explode(',', strtolower((string) config('cafeqr.dominio_institucional')))
        )));

        $correo = strtolower((string) $value);
        $parte  = substr(strrchr($correo, '@') ?: '', 1);

        foreach ($dominios as $dominio) {
            if ($parte === $dominio || str_ends_with($parte, '.' . $dominio)) {
                return;
            }
        }

        $fail('El correo debe pertenecer a un dominio permitido (@' . implode(', @', $dominios) . ').');
    }
}