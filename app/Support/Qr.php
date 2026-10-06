<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Qr
{
    // Devuelve el QR como imagen lista para usar en <img src="...">
    public static function dataUri(string $texto): string
    {
        $opciones = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64'    => true,
            'eccLevel'        => EccLevel::M,
            'addQuietzone'    => true,
        ]);

        return (new QRCode($opciones))->render($texto);
    }
}