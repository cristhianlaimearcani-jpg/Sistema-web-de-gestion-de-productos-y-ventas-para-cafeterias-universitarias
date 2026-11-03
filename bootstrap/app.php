<?php

use App\Http\Middleware\Rol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => Rol::class,
        ]);
        $middleware->web(append: [\App\Http\Middleware\LimpiarPedidosAbandonados::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // 419 "Página caducada": en vez de la pantalla en blanco, vuelve al formulario con un aviso
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }
            if ($request->routeIs('logout')) {
                return redirect('/');
            }

            return redirect()->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'password_actual', 'codigo']))
                ->with('error', 'La página estuvo abierta mucho tiempo o el servidor se reinició. Ya la recargamos: vuelve a intentarlo.');
        });
    })->create();
