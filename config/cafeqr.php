<?php

return [
    'dominio_institucional' => env('DOMINIO_INSTITUCIONAL', 'univalle.edu'),

    // 'demo'  = QR de prueba y botones para simular el pago
    // 'banco' = QR real de tu banco; la cafetería confirma cuando el dinero llega
    'pago_modo'    => env('PAGO_MODO', 'demo'),
    'pago_minutos' => (int) env('PAGO_MINUTOS', 30),   // minutos de validez del QR

    // Modo "banco": imagen del QR de cobro de tu app bancaria (dentro de la carpeta public)
    'qr_cobro'      => env('QR_COBRO', 'img/qr-cobro.png'),
    'banco_nombre'  => env('BANCO_NOMBRE', ''),
    'banco_titular' => env('BANCO_TITULAR', ''),

    // Datos del emisor que salen en la factura
    'emisor_nombre'    => env('EMISOR_NOMBRE', 'Cafetería Universitaria CafeQR'),
    'emisor_nit'       => env('EMISOR_NIT', 'S/N (demostración)'),
    'emisor_direccion' => env('EMISOR_DIRECCION', 'Universidad Privada del Valle, La Paz - Bolivia'),
];