<?php

namespace App\Support;

class NumeroALetras
{
    private const UNIDADES = [
        'cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete',
        'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés',
        'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve',
    ];

    private const DECENAS = [
        3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta',
        7 => 'setenta', 8 => 'ochenta', 9 => 'noventa',
    ];

    private const CENTENAS = [
        1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos',
        5 => 'quinientos', 6 => 'seiscientos', 7 => 'setecientos', 8 => 'ochocientos',
        9 => 'novecientos',
    ];

    // Ej.: 23 -> "Son: Veintitrés 00/100 bolivianos"
    public static function bolivianos(float|int|string $monto): string
    {
        $monto = round((float) $monto, 2);
        $entero = (int) floor($monto);
        $centavos = (int) round(($monto - $entero) * 100);

        if ($centavos === 100) {
            $entero++;
            $centavos = 0;
        }

        $texto = self::texto($entero);
        $texto = mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);

        return 'Son: ' . $texto . ' ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100 bolivianos';
    }

    public static function texto(int $n, bool $apocope = false): string
    {
        if ($n < 30) {
            if ($apocope && $n === 1) {
                return 'un';
            }
            if ($apocope && $n === 21) {
                return 'veintiún';
            }

            return self::UNIDADES[$n];
        }

        if ($n < 100) {
            $unidad = $n % 10;

            return self::DECENAS[intdiv($n, 10)] . ($unidad ? ' y ' . self::texto($unidad, $apocope) : '');
        }

        if ($n === 100) {
            return 'cien';
        }

        if ($n < 1000) {
            $resto = $n % 100;

            return self::CENTENAS[intdiv($n, 100)] . ($resto ? ' ' . self::texto($resto, $apocope) : '');
        }

        if ($n < 1000000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;
            $prefijo = $miles === 1 ? 'mil' : self::texto($miles, true) . ' mil';

            return $prefijo . ($resto ? ' ' . self::texto($resto, $apocope) : '');
        }

        $millones = intdiv($n, 1000000);
        $resto = $n % 1000000;
        $prefijo = $millones === 1 ? 'un millón' : self::texto($millones, true) . ' millones';

        return $prefijo . ($resto ? ' ' . self::texto($resto, $apocope) : '');
    }
}