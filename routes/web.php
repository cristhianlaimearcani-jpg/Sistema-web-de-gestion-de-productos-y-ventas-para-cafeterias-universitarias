<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SeguimientoController;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// "App del banco" de demostración: se abre al escanear el QR de prueba (no requiere sesión)
Route::get('/pagar/{referencia}', [PagoController::class, 'escanear'])->name('pago.escanear');
Route::post('/pagar/{referencia}', [PagoController::class, 'ejecutar'])->name('pago.ejecutar');

// Solo para quienes NO han iniciado sesión
Route::middleware('guest')->group(function () {
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/olvide-mi-contrasena', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/olvide-mi-contrasena', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'reset'])->name('password.update');
});

// Solo para quienes SÍ iniciaron sesión
Route::middleware('auth')->group(function () {
    // Menú por secciones
    Route::get('/dashboard', function () {
        $productos = Producto::orderBy('nombre')->get()->groupBy('categoria');

        $secciones = collect(Producto::CATEGORIAS)
            ->filter(fn ($icono, $nombre) => $productos->has($nombre));

        return view('dashboard', compact('productos', 'secciones'));
    })->name('dashboard');

    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil.show');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::delete('/perfil/foto', [PerfilController::class, 'eliminarFoto'])->name('perfil.foto.eliminar');

    // Historial
    Route::get('/mis-pedidos', [PedidoController::class, 'historial'])->name('pedidos.historial');

    // HU-09: armar el pedido
    Route::get('/mi-pedido', [PedidoController::class, 'actual'])->name('pedido.actual');
    Route::post('/mi-pedido/agregar/{producto}', [PedidoController::class, 'agregar'])->name('pedido.agregar');
    Route::patch('/mi-pedido/items/{detalle}', [PedidoController::class, 'actualizar'])->name('pedido.actualizar');
    Route::delete('/mi-pedido/items/{detalle}', [PedidoController::class, 'quitar'])->name('pedido.quitar');
    Route::delete('/mi-pedido', [PedidoController::class, 'vaciar'])->name('pedido.vaciar');
    Route::get('/mi-pedido/confirmar', [PedidoController::class, 'confirmar'])->name('pedido.confirmar');

    // HU-10 y HU-11: pago con QR
    Route::post('/mi-pedido/pagar', [PagoController::class, 'iniciar'])->name('pedido.pagar');
    Route::get('/pago/{pago}', [PagoController::class, 'show'])->name('pago.show');
    Route::get('/pago/{pago}/estado', [PagoController::class, 'estado'])->name('pago.estado');
    Route::post('/pago/{pago}/reintentar', [PagoController::class, 'reintentar'])->name('pago.reintentar');
    Route::post('/pago/{pago}/ya-pague', [PagoController::class, 'yaPague'])->name('pago.yapague');
    Route::post('/pago/{pago}/simular', [PagoController::class, 'simular'])->name('pago.simular');

    // Código que llega al correo, y "¡Gracias por tu compra!" con la factura
    Route::post('/pago/{pago}/codigo', [PagoController::class, 'comprobarCodigo'])->name('pago.codigo');
    Route::post('/pago/{pago}/codigo/reenviar', [PagoController::class, 'reenviarCodigo'])->name('pago.codigo.reenviar');
    Route::get('/pedido/{pedido}/listo', [PagoController::class, 'exito'])->name('pedido.exito');
    Route::get('/factura/{pedido}', [FacturaController::class, 'show'])->name('factura.show');

    // HU-14: seguimiento del pedido (el estudiante)
    Route::get('/pedido/{pedido}/seguimiento', [SeguimientoController::class, 'show'])->name('pedido.seguimiento');
    Route::get('/pedido/{pedido}/estado', [SeguimientoController::class, 'estado'])->name('pedido.estado');

    // Caja de la cafetería (solo usuarios con rol "encargado")
    Route::get('/caja/pagos', [CajaController::class, 'index'])->name('caja.pagos');
    Route::post('/caja/pagos/{pago}/recibido', [CajaController::class, 'recibido'])->name('caja.recibido');
    Route::post('/caja/pagos/{pago}/rechazar', [CajaController::class, 'rechazar'])->name('caja.rechazar');

    // HU-12 y HU-13: validar el código de recojo y entregar
    Route::get('/caja/entregas', [EntregaController::class, 'index'])->name('caja.entregas');
    Route::post('/caja/entregas/{pedido}/estado', [EntregaController::class, 'cambiar'])->name('caja.entregas.estado');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});