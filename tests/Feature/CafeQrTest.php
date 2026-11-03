<?php

namespace Tests\Feature;

use App\Mail\CafeQrMail;
use App\Models\Correo;
use App\Services\Notificador;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\CuponSeeder;
use Database\Seeders\ProductoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CafeQrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cafeqr.pago_modo' => 'demo', 'cafeqr.dominio' => 'univalle.edu']);
        $this->seed(AdminSeeder::class);
        $this->seed(ProductoSeeder::class);
        $this->seed(CuponSeeder::class);
        Mail::fake();
    }

    private function usuario(string $rol = 'cliente', string $correo = null): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'name' => 'Persona Prueba',
            'email' => $correo ?? "persona{$n}@est.univalle.edu",
            'password' => 'Clave12345',
            'rol' => $rol,
        ]);
    }

    private function admin(): User
    {
        return User::where('rol', 'administrador')->firstOrFail();
    }

    /** Cliente con un pedido ya confirmado hacia el método indicado. Devuelve [cliente, pago]. */
    private function pedidoHastaPago(string $metodo): array
    {
        $cliente = $this->usuario();
        $producto = Producto::where('disponible', true)->first();

        $this->actingAs($cliente)->post(route('pedido.agregar', $producto), ['cantidad' => 2])->assertSessionHasNoErrors();
        $this->actingAs($cliente)->get(route('pedido.confirmar'))->assertOk()->assertSee('Último paso');
        $this->actingAs($cliente)->post(route('pedido.pagar'), [
            'metodo' => $metodo, 'razon_social' => 'Juan Perez', 'nit_ci' => '1234567',
        ])->assertRedirect();

        $pago = Pago::latest('id')->firstOrFail();

        return [$cliente, $pago];
    }

    /* ------------------------------ Registro ------------------------------ */

    public function test_registro_valido_crea_cliente(): void
    {
        $this->post(route('registro.guardar'), [
            'name' => 'María José Peña', 'email' => 'maria@est.univalle.edu', 'telefono' => '71234567',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertSessionHasNoErrors();

        $this->assertSame('cliente', User::where('email', 'maria@est.univalle.edu')->value('rol'));
        $this->assertAuthenticated();
    }

    public function test_el_nombre_no_acepta_numeros(): void
    {
        foreach (['Juan123', '12345', 'Ana 2', 'J@n', 'x'] as $nombre) {
            $this->post(route('registro.guardar'), [
                'name' => $nombre, 'email' => 'a'.md5($nombre).'@est.univalle.edu',
                'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
            ])->assertSessionHasErrors('name');
        }
        $this->assertGuest();
    }

    public function test_registro_valida_correo_telefono_y_clave(): void
    {
        $base = ['name' => 'Ana Lopez', 'email' => 'ana@est.univalle.edu', 'password' => 'Clave12345', 'password_confirmation' => 'Clave12345'];

        $this->post(route('registro.guardar'), ['email' => 'ana@gmail.com'] + $base)->assertSessionHasErrors('email');
        $this->post(route('registro.guardar'), ['email' => 'no-es-correo'] + $base)->assertSessionHasErrors('email');
        $this->post(route('registro.guardar'), ['telefono' => '12345678'] + $base)->assertSessionHasErrors('telefono');
        $this->post(route('registro.guardar'), ['telefono' => 'abc'] + $base)->assertSessionHasErrors('telefono');
        $this->post(route('registro.guardar'), ['telefono' => '41234567'] + $base)->assertSessionHasErrors('telefono');
        $this->post(route('registro.guardar'), ['telefono' => '5123456'] + $base)->assertSessionHasErrors('telefono');
        $this->post(route('registro.guardar'), ['password' => 'corta1', 'password_confirmation' => 'corta1'] + $base)->assertSessionHasErrors('password');
        $this->post(route('registro.guardar'), ['password' => 'sololetras', 'password_confirmation' => 'sololetras'] + $base)->assertSessionHasErrors('password');
        $this->post(route('registro.guardar'), ['password_confirmation' => 'otra'] + $base)->assertSessionHasErrors('password');
    }

    public function test_correo_repetido_no_se_registra(): void
    {
        $this->usuario('cliente', 'repe@est.univalle.edu');
        $this->post(route('registro.guardar'), [
            'name' => 'Otra Persona', 'email' => 'repe@est.univalle.edu',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_correcto_e_incorrecto(): void
    {
        $this->usuario('cliente', 'login@est.univalle.edu');
        $this->post(route('login.entrar'), ['email' => 'login@est.univalle.edu', 'password' => 'mala'])->assertSessionHasErrors();
        $this->assertGuest();
        $this->post(route('login.entrar'), ['email' => 'login@est.univalle.edu', 'password' => 'Clave12345'])->assertRedirect();
        $this->assertAuthenticated();
    }

    /* ------------------------------ Roles ------------------------------ */

    public function test_el_admin_del_seeder_existe_y_entra(): void
    {
        $admin = $this->admin();
        $this->assertSame(config('cafeqr.admin.email'), $admin->email);
        $this->post(route('login.entrar'), ['email' => $admin->email, 'password' => config('cafeqr.admin.password')]);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_cliente_solo_ve_menu_y_pide(): void
    {
        $cliente = $this->usuario();
        $this->actingAs($cliente)->get(route('dashboard'))->assertOk();
        $this->actingAs($cliente)->get(route('pedidos.historial'))->assertOk();
    }

    public function test_cliente_no_entra_a_gestion(): void
    {
        $cliente = $this->usuario();
        foreach (['productos.index', 'productos.create', 'caja.pagos', 'entregas.index', 'admin.panel', 'admin.usuarios', 'admin.facturas', 'admin.pedidos'] as $ruta) {
            $this->actingAs($cliente)->get(route($ruta))->assertRedirect(route('dashboard'));
        }
    }

    public function test_empleado_gestiona_pero_no_es_admin(): void
    {
        $empleado = $this->usuario('empleado');
        foreach (['productos.index', 'productos.create', 'caja.pagos', 'entregas.index'] as $ruta) {
            $this->actingAs($empleado)->get(route($ruta))->assertOk();
        }
        foreach (['admin.panel', 'admin.usuarios', 'admin.facturas', 'admin.empleado.crear'] as $ruta) {
            $this->actingAs($empleado)->get(route($ruta))->assertRedirect();
        }
        $this->actingAs($empleado)->get(route('pedido.actual'))->assertRedirect();
    }

    public function test_admin_ve_todo(): void
    {
        $admin = $this->admin();
        foreach (['admin.panel', 'admin.pedidos', 'admin.facturas', 'admin.usuarios', 'admin.empleado.crear',
            'productos.index', 'productos.create', 'caja.pagos', 'entregas.index'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk();
        }
    }

    public function test_invitado_va_al_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.panel'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
        $this->get(route('registro'))->assertOk();
    }

    public function test_inicio_lleva_al_login_y_el_usuario_va_a_su_inicio(): void
    {
        // Ya no hay página de presentación: el visitante va directo al login
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs($this->usuario('cliente'))->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_login_y_registro_sin_panel_de_presentacion_y_con_animacion(): void
    {
        foreach ([route('login'), route('registro'), route('password.request')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('data-auth-tarjeta', false)
                ->assertSee('con-animacion', false)
                ->assertSee('data-tema-boton', false)
                ->assertDontSee('auth-lado', false)
                ->assertDontSee('Tu café, sin hacer fila', false);
        }
        $this->get(route('login'))->assertSee('data-transicion', false)->assertSee('Entrando…', false);
        $this->get(route('registro'))->assertSee('data-transicion="atras"', false)->assertSee('Creando tu cuenta…', false);
    }

    public function test_menu_usa_imagenes_y_boton_de_tema(): void
    {
        $respuesta = $this->actingAs($this->usuario('cliente'))->get(route('dashboard'))->assertOk();
        $respuesta->assertSee('data-tema-boton', false)
            ->assertSee('categoria-bebidas-calientes.webp', false)
            ->assertSee('class="stepper"', false);
        $this->assertCount(8, Producto::destacados(8));
    }

    public function test_pagina_caducada_vuelve_al_formulario_con_aviso(): void
    {
        // Simula un formulario enviado con un token viejo (error 419)
        $peticion = \Illuminate\Http\Request::create(route('registro.guardar'), 'POST', ['name' => 'Lucia Rios', 'password' => 'Clave12345']);
        $peticion->headers->set('referer', route('registro'));
        $peticion->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $peticion);

        $respuesta = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($peticion, new \Illuminate\Session\TokenMismatchException('CSRF token mismatch.'));

        $this->assertSame(302, $respuesta->getStatusCode());
        $this->assertSame(route('registro'), $respuesta->headers->get('Location'));
        $this->assertNotEmpty(session('error'));
        $this->assertSame('Lucia Rios', session()->getOldInput('name'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_celular_entel_nuevo_que_empieza_con_5_se_registra(): void
    {
        $this->post(route('registro.guardar'), [
            'name' => 'Saul Gutierrez Villacorta', 'email' => 'saul@est.univalle.edu', 'telefono' => '5383 2949',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'saul@est.univalle.edu', 'telefono' => '53832949']);

        auth()->logout();
        $this->get(route('registro'))->assertSee('data-ver-clave', false)->assertSee('Las dos coinciden');
    }

    public function test_paginas_de_error_con_diseno_de_cafeqr(): void
    {
        $this->get('/esta-pagina-no-existe')->assertNotFound()->assertSee('Esta página no existe')->assertSee('Ir al inicio');

        $otro = $this->usuario('cliente');
        $pedido = Pedido::create(['user_id' => $otro->id, 'estado' => 'carrito', 'total' => 0]);
        $this->actingAs($this->usuario('cliente'))->get(route('pedido.seguimiento', $pedido))
            ->assertForbidden()->assertSee('No puedes entrar aquí');
    }

    /* ------------------------------ Compra segura ------------------------------ */

    public function test_el_mismo_intento_de_compra_no_crea_dos_pedidos(): void
    {
        $cliente = $this->usuario();
        $producto = Producto::disponibles()->first();
        $this->actingAs($cliente)->post(route('pedido.agregar', $producto));
        $this->actingAs($cliente)->get(route('pedido.confirmar'))->assertOk()->assertSee('name="clave_intento"', false);

        $datos = ['metodo' => 'qr', 'clave_intento' => '7a1c2b3d-0000-4000-8000-123456789abc'];
        $primero = $this->actingAs($cliente)->post(route('pedido.pagar'), $datos);
        $pago = Pago::latest('id')->firstOrFail();
        $primero->assertRedirect(route('pago.show', $pago));

        // El formulario llega otra vez (doble clic, recarga): devuelve el mismo pedido.
        $this->actingAs($cliente)->post(route('pedido.pagar'), $datos)->assertRedirect(route('pago.show', $pago))
            ->assertSessionHas('exito', fn ($m) => str_contains($m, 'ya estaba registrado'));
        $this->assertSame(1, Pedido::where('user_id', $cliente->id)->count());
        $this->assertSame(1, Pago::count());
        $this->assertSame($producto->stock - 1, $producto->fresh()->stock, 'El stock se descontó una sola vez');
    }

    public function test_aviso_de_compra_repetida(): void
    {
        $cliente = $this->usuario();
        $producto = Producto::disponibles()->first();

        // Primer pedido, pagado.
        $this->actingAs($cliente)->post(route('pedido.agregar', $producto), ['cantidad' => 2]);
        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'qr', 'clave_intento' => 'a-1']);
        $primero = Pedido::latest('id')->first();
        $this->actingAs($cliente)->post(route('pago.simular', $primero->pagoActivo()));
        $this->assertSame(Pedido::PAGADO, $primero->fresh()->estado);

        // Arma el mismo pedido otra vez.
        $this->actingAs($cliente)->post(route('pedido.agregar', $producto), ['cantidad' => 2]);
        $this->actingAs($cliente)->get(route('pedido.confirmar'))->assertOk()
            ->assertSee('Ya tienes un pedido igual. ¿Deseas ver ese pedido o comprar otro?')
            ->assertSee('Pedido #'.$primero->id);

        // Si paga sin elegir, vuelve al aviso y no se crea nada.
        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'qr', 'clave_intento' => 'a-2'])
            ->assertRedirect(route('pedido.confirmar'))->assertSessionHas('repetido_aviso');
        $this->assertSame(0, Pedido::where('estado', Pedido::PENDIENTE_PAGO)->count());

        // Eligió "comprar otro": ahora sí se registra.
        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'qr', 'clave_intento' => 'a-3', 'repetir' => '1'])->assertRedirect();
        $this->assertSame(1, Pedido::where('estado', Pedido::PENDIENTE_PAGO)->count());

        // Con otros productos no hay aviso.
        $otro = Producto::disponibles()->where('id', '!=', $producto->id)->first();
        $this->actingAs($cliente)->post(route('pedido.modificar'));
        $this->actingAs($cliente)->post(route('pedido.agregar', $otro));
        $this->actingAs($cliente)->get(route('pedido.confirmar'))->assertDontSee('Ya tienes un pedido igual');
    }

    public function test_cliente_cancela_solo_antes_de_la_preparacion(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $this->actingAs($cliente)->post(route('pago.simular', $pago));
        $pedido = $pago->pedido->fresh();

        $this->actingAs($this->usuario('empleado'))->post(route('entregas.preparar', $pedido), ['minutos' => 10]);
        $this->actingAs($cliente)->get(route('pedido.seguimiento', $pedido))->assertOk()
            ->assertDontSee('¿Quieres cancelar este pedido?')->assertSee('ya no se puede cancelar');
        $this->actingAs($cliente)->post(route('pedido.cancelar', $pedido))->assertSessionHas('error');
        $this->assertSame(Pedido::EN_PREPARACION, $pedido->fresh()->estado);
    }

    public function test_mis_pedidos_se_filtran_por_estado(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $this->actingAs($cliente)->post(route('pedido.cancelar', $pago->pedido));

        $this->actingAs($cliente)->get(route('pedidos.historial'))->assertOk()->assertSee('Cancelados');
        $this->actingAs($cliente)->get(route('pedidos.historial', ['ver' => 'cancelados']))->assertOk()->assertSee('Pedido #'.$pago->pedido_id);
        $this->actingAs($cliente)->get(route('pedidos.historial', ['ver' => 'pendientes']))->assertOk()->assertDontSee('Pedido #'.$pago->pedido_id.'<');
    }

    /* ------------------------------ Stock ------------------------------ */

    public function test_stock_se_descuenta_al_comprar_y_vuelve_al_cancelar(): void
    {
        $cliente = $this->usuario();
        $producto = Producto::disponibles()->first();
        $producto->update(['stock' => 5]);

        $this->actingAs($cliente)->post(route('pedido.agregar', $producto), ['cantidad' => 6])->assertSessionHas('error');
        $this->actingAs($cliente)->post(route('pedido.agregar', $producto), ['cantidad' => 3])->assertSessionHas('exito');
        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'efectivo', 'clave_intento' => 's-1'])->assertRedirect();
        $this->assertSame(2, $producto->fresh()->stock);

        $pedido = Pedido::latest('id')->first();
        $this->actingAs($cliente)->post(route('pedido.modificar'));
        $this->assertSame(5, $producto->fresh()->stock, 'Al modificar, las unidades vuelven');

        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'efectivo', 'clave_intento' => 's-2'])->assertRedirect();
        $this->assertSame(2, $producto->fresh()->stock);
        $this->actingAs($cliente)->post(route('pedido.cancelar', $pedido->fresh()));
        $this->assertSame(5, $producto->fresh()->stock, 'Al cancelar, las unidades vuelven');
        // Cancelar dos veces no devuelve dos veces
        $pedido->fresh()->cancelar('personal', 'prueba');
        $this->assertSame(5, $producto->fresh()->stock);

        // Sin stock: agotado en el menú y no se puede pedir
        $producto->update(['stock' => 0]);
        $this->assertTrue($producto->fresh()->agotado);
        $this->actingAs($cliente)->post(route('pedido.agregar', $producto))->assertSessionHas('error');
        $this->actingAs($cliente)->get(route('dashboard'))->assertSee('Agotado');
    }

    public function test_productos_numerados_con_stock_y_ajuste_rapido(): void
    {
        $admin = $this->admin();
        $producto = Producto::orderBy('categoria')->orderBy('nombre')->first();

        $this->actingAs($admin)->get(route('productos.index'))->assertOk()
            ->assertSee('Unidades en stock')->assertSee('<td class="num-fila">1</td>', false)->assertSee('Vendidos');

        $this->actingAs($admin)->post(route('productos.stock', $producto), ['stock' => 7, 'accion' => 'fijar'])->assertSessionHas('exito');
        $this->assertSame(7, $producto->fresh()->stock);
        $this->actingAs($admin)->post(route('productos.stock', $producto), ['stock' => 10, 'accion' => 'sumar']);
        $this->assertSame(17, $producto->fresh()->stock);
        $this->actingAs($admin)->post(route('productos.stock', $producto), ['stock' => -2])->assertSessionHasErrors('stock');
        $this->actingAs($this->usuario())->post(route('productos.stock', $producto), ['stock' => 1])->assertRedirect();
        $this->assertSame(17, $producto->fresh()->stock);
    }

    /* ------------------------------ Soporte ------------------------------ */

    public function test_soporte_cliente_y_personal(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');

        $this->actingAs($cliente)->get(route('soporte.index', ['pedido' => $pago->pedido_id, 'tipo' => 'pago']))->assertOk()->assertSee('Preguntas frecuentes');
        $this->actingAs($cliente)->post(route('soporte.guardar'), ['tipo' => 'pago', 'asunto' => 'x', 'mensaje' => 'corto'])
            ->assertSessionHasErrors(['asunto', 'mensaje']);
        $this->actingAs($cliente)->post(route('soporte.guardar'), [
            'tipo' => 'pago', 'pedido_id' => $pago->pedido_id, 'asunto' => 'Pagué y no aparece', 'mensaje' => 'Pagué con QR y la pantalla no cambió.',
        ])->assertRedirect();
        $ticket = \App\Models\Ticket::firstOrFail();
        $this->assertSame('abierto', $ticket->estado);

        // Otro cliente no puede verlo
        $this->actingAs($this->usuario())->get(route('soporte.show', $ticket))->assertForbidden();

        // El empleado lo ve en su lista y responde
        $empleado = $this->usuario('empleado');
        $this->actingAs($empleado)->get(route('soporte.gestion'))->assertOk()->assertSee('Pagué y no aparece');
        $this->actingAs($empleado)->get(route('entregas.index'))->assertSee('class="contador">1<', false);
        $this->actingAs($empleado)->post(route('soporte.responder', $ticket), ['mensaje' => 'Ya lo revisamos, tu pago llegó.', 'estado' => 'resuelto'])
            ->assertSessionHas('exito');
        $this->assertSame('resuelto', $ticket->fresh()->estado);
        $this->assertSame(1, $this->correosDe('emails.soporte-respuesta')->count());

        // El cliente ve la respuesta; si vuelve a escribir, se reabre
        $this->actingAs($cliente)->get(route('soporte.show', $ticket))->assertOk()->assertSee('Ya lo revisamos, tu pago llegó.');
        $this->actingAs($cliente)->post(route('soporte.responder', $ticket), ['mensaje' => 'Gracias, pero me falta la factura.']);
        $this->assertSame('abierto', $ticket->fresh()->estado);
        $this->actingAs($cliente)->post(route('soporte.resolver', $ticket));
        $this->assertSame('resuelto', $ticket->fresh()->estado);
    }

    /* ------------------------------ Estadísticas ------------------------------ */

    public function test_estadisticas_del_negocio(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $this->actingAs($cliente)->post(route('pago.simular', $pago));
        [$cliente2, $pago2] = $this->pedidoHastaPago('efectivo');
        $this->actingAs($this->usuario('empleado'))->post(route('caja.recibido', $pago2));

        $admin = $this->admin();
        foreach (['hoy', '7', '30', 'mes', 'anio', 'todo'] as $periodo) {
            $this->actingAs($admin)->get(route('admin.estadisticas', ['periodo' => $periodo]))->assertOk();
        }
        $total = $pago->pedido->fresh()->total + $pago2->pedido->fresh()->total;
        $this->actingAs($admin)->get(route('admin.estadisticas', ['periodo' => 'hoy']))
            ->assertSee('Estadísticas del negocio')->assertSee('Bs '.number_format($total, 2))
            ->assertSee('Productos más vendidos')->assertSee('Horas pico')->assertSee('Formas de pago');
        $this->actingAs($admin)->get(route('admin.estadisticas', ['desde' => now()->subDays(3)->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]))->assertOk();

        $this->actingAs($this->usuario('empleado'))->get(route('admin.estadisticas'))->assertRedirect();
    }

    public function test_botones_de_compra_protegidos(): void
    {
        $cliente = $this->usuario();
        $this->actingAs($cliente)->get(route('dashboard'))->assertSee('Procesando…')->assertSee('data-sin-proteger', false);
    }

    public function test_pedido_sin_pagar_se_cancela_solo_y_devuelve_stock(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $pedido = $pago->pedido;
        $producto = $pedido->detalles()->first()->producto;
        $stockReservado = $producto->fresh()->stock;

        // Todavía no pasó el tiempo: no se cancela
        $this->assertSame(0, Pedido::cancelarAbandonados());

        $this->travel(config('cafeqr.minutos_sin_pagar') + 5)->minutes();
        $this->assertSame(1, Pedido::cancelarAbandonados());
        $this->assertSame(Pedido::CANCELADO, $pedido->fresh()->estado);
        $this->assertSame('sistema', $pedido->fresh()->cancelado_por);
        $this->assertSame($stockReservado + 2, $producto->fresh()->stock);
        $this->assertFalse((bool) $pedido->fresh()->reembolso_pendiente);

        // Un pago que la caja debe verificar (ya pagó) no se cancela
        config(['cafeqr.pago_modo' => 'banco']);
        [$cliente2, $pago2] = $this->pedidoHastaPago('qr');
        $this->actingAs($cliente2)->post(route('pago.ya-pague', $pago2));
        $this->travel(config('cafeqr.minutos_sin_pagar') + 5)->minutes();
        $this->assertSame(0, Pedido::cancelarAbandonados());
        $this->assertSame(Pedido::PENDIENTE_PAGO, $pago2->pedido->fresh()->estado);
    }

    /** Recorre TODAS las páginas (GET) como cada tipo de usuario: ninguna debe dar error 500. */
    public function test_ninguna_pagina_da_error_500(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $this->actingAs($cliente)->post(route('pago.simular', $pago));
        [$cliente2, $pago2] = $this->pedidoHastaPago('efectivo');
        $pedido = $pago->pedido->fresh();
        $this->actingAs($cliente)->post(route('soporte.guardar'), ['tipo' => 'otro', 'asunto' => 'Una consulta', 'mensaje' => 'Hola, tengo una consulta general.']);
        $ticket = \App\Models\Ticket::first();

        $valores = [
            'pedido' => [$pedido->id, $pago2->pedido_id, 999999],
            'pago' => [$pago->id, $pago2->id, 999999],
            'producto' => [Producto::first()->id, 999999],
            'ticket' => [$ticket->id, 999999],
            'referencia' => [$pago->referencia, $pago2->referencia, 'NOEXISTE'],
            'token' => ['token-falso'],
            'usuario' => [$cliente->id, 999999],
            'correo' => [999999],
            'detalle' => [999999],
            'path' => ['nada.txt'],
        ];

        $urls = [];
        foreach (app('router')->getRoutes() as $ruta) {
            if (! in_array('GET', $ruta->methods(), true) || str_starts_with($ruta->uri(), 'storage') || $ruta->uri() === 'up') {
                continue;
            }
            $combos = [[]];
            foreach ($ruta->parameterNames() as $param) {
                $nuevos = [];
                foreach ($combos as $c) {
                    foreach ($valores[$param] ?? [1] as $v) {
                        $nuevos[] = $c + [$param => $v];
                    }
                }
                $combos = $nuevos;
            }
            foreach ($combos as $c) {
                $uri = $ruta->uri();
                foreach ($c as $k => $v) {
                    $uri = str_replace('{'.$k.'}', (string) $v, $uri);
                }
                $urls[] = '/'.ltrim($uri, '/');
            }
        }
        $urls = array_merge($urls, ['/mis-pedidos?ver=cancelados', '/mis-pedidos?ver=xx', '/admin/estadisticas?periodo=hoy',
            '/admin/estadisticas?desde=2026-13-45&hasta=abc', '/admin/estadisticas?desde=2026-10-30&hasta=2026-10-01',
            '/gestion/productos?estado=stock_bajo', '/gestion/soporte?estado=resuelto&tipo=pago', '/dashboard?q=%27%22<script>',
            '/mi-pedido/continuar?pedido=999999', '/soporte?pedido=999999&tipo=xx']);

        $usuarios = ['invitado' => null, 'cliente' => $cliente, 'otro cliente' => $cliente2,
            'empleado' => $this->usuario('empleado'), 'admin' => $this->admin()];

        foreach ($usuarios as $quien => $usuario) {
            foreach ($urls as $url) {
                auth()->logout();
                $this->flushSession();
                $r = $usuario ? $this->actingAs($usuario)->get($url) : $this->get($url);
                $this->assertLessThan(500, $r->getStatusCode(), "Error {$r->getStatusCode()} en {$url} como {$quien}");
            }
        }
    }

    /** Envía TODOS los formularios (POST/PUT/PATCH/DELETE) vacíos o con datos raros: nunca error 500. */
    public function test_ningun_formulario_da_error_500(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        [$cliente2, $pago2] = $this->pedidoHastaPago('efectivo');
        $pedido = $pago->pedido->fresh();
        $this->actingAs($cliente)->post(route('soporte.guardar'), ['tipo' => 'otro', 'asunto' => 'Una consulta', 'mensaje' => 'Hola, tengo una consulta general.']);
        $ticket = \App\Models\Ticket::first();
        $detalle = $pedido->detalles()->first();

        $valores = [
            'pedido' => [$pedido->id, 999999], 'pago' => [$pago->id, $pago2->id, 999999], 'producto' => [Producto::latest('id')->first()->id, 999999],
            'ticket' => [$ticket->id], 'referencia' => [$pago->referencia, 'NOEXISTE'], 'usuario' => [$cliente2->id, 999999],
            'correo' => [999999], 'detalle' => [$detalle->id, 999999], 'path' => ['x'],
        ];
        $datosRaros = [[], ['cantidad' => 'abc', 'metodo' => 'bitcoin', 'stock' => '-5', 'minutos' => '9999', 'transaccion' => '<script>', 'estado' => 'zzz', 'mensaje' => str_repeat('x', 3000), 'clave_intento' => str_repeat('a', 300), 'rol' => 'dios', 'codigo' => '1', 'cupon' => str_repeat('Z', 50)]];

        $usuarios = ['invitado' => null, 'cliente' => $cliente, 'empleado' => $this->usuario('empleado'), 'admin' => $this->admin()];

        foreach (app('router')->getRoutes() as $ruta) {
            $metodo = collect($ruta->methods())->first(fn ($m) => in_array($m, ['POST', 'PUT', 'PATCH', 'DELETE'], true));
            if (! $metodo || str_starts_with($ruta->uri(), 'storage') || in_array($ruta->getName(), ['logout', 'admin.usuarios.eliminar', 'productos.destroy'], true)) {
                continue;
            }
            $uri = $ruta->uri();
            foreach ($ruta->parameterNames() as $param) {
                $uri = str_replace('{'.$param.'}', (string) ($valores[$param][0] ?? 1), $uri);
            }
            foreach ($usuarios as $quien => $usuario) {
                foreach ($datosRaros as $datos) {
                    auth()->logout();
                    $this->flushSession();
                    $r = $usuario ? $this->actingAs($usuario)->call($metodo, '/'.$uri, $datos) : $this->call($metodo, '/'.$uri, $datos);
                    $this->assertLessThan(500, $r->getStatusCode(), "Error {$r->getStatusCode()} en {$metodo} /{$uri} como {$quien}");
                }
            }
        }
    }

    /* ------------------------------ Admin: empleados ------------------------------ */

    public function test_admin_crea_y_elimina_empleado(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.empleado.guardar'), [
            'name' => 'Pedro Empleado', 'email' => 'pedro@cafeqr.test', 'telefono' => '61234567',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertSessionHasNoErrors();

        $empleado = User::where('email', 'pedro@cafeqr.test')->firstOrFail();
        $this->assertSame('empleado', $empleado->rol);

        $this->actingAs($admin)->post(route('admin.empleado.guardar'), [
            'name' => 'Pedro 99', 'email' => 'p2@cafeqr.test',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertSessionHasErrors('name');

        $this->actingAs($admin)->delete(route('admin.usuarios.eliminar', $empleado))->assertRedirect();
        $this->assertDatabaseMissing('users', ['email' => 'pedro@cafeqr.test']);
    }

    public function test_admin_cancela_pedidos_y_elimina_cliente_con_pedidos(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('efectivo');
        $pedido = $pago->pedido;
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.pedidos'))->assertOk()->assertSee('Cancelar');
        $this->actingAs($admin)->post(route('entregas.cancelar', $pedido), ['motivo' => 'ab'])->assertSessionHasErrors('motivo');
        $this->actingAs($admin)->post(route('entregas.cancelar', $pedido), ['motivo' => 'Prueba del administrador'])->assertSessionHasNoErrors();
        $this->assertSame(Pedido::CANCELADO, $pedido->fresh()->estado);
        Mail::assertSent(CafeQrMail::class, fn ($m) => $m->hasTo($cliente->email) && str_contains($m->asunto, 'cancelado'));

        $this->actingAs($admin)->get(route('admin.usuarios'))->assertOk();
        $this->actingAs($admin)->delete(route('admin.usuarios.eliminar', $cliente))->assertSessionHas('exito');
        $this->assertDatabaseMissing('users', ['id' => $cliente->id]);
        $this->assertDatabaseMissing('pedidos', ['id' => $pedido->id]);
        $this->assertDatabaseMissing('pagos', ['id' => $pago->id]);

        // puede volver a registrarse con el mismo correo
        auth()->logout();
        $this->post(route('registro.guardar'), [
            'name' => 'Persona Prueba', 'email' => $cliente->email,
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertSessionHasNoErrors();
    }

    public function test_admin_no_puede_eliminarse_ni_cambiarse_el_rol(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete(route('admin.usuarios.eliminar', $admin));
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->actingAs($admin)->post(route('admin.usuarios.rol', $admin), ['rol' => 'cliente']);
        $this->assertSame('administrador', $admin->fresh()->rol);
    }

    /* ------------------------------ Productos ------------------------------ */

    public function test_empleado_crea_edita_oculta_y_elimina_productos(): void
    {
        $empleado = $this->usuario('empleado');

        $this->actingAs($empleado)->post(route('productos.store'), [
            'nombre' => 'Café especial', 'precio' => '12.50', 'categoria' => array_key_first(Producto::CATEGORIAS), 'disponible' => '1', 'stock' => '12',
        ])->assertSessionHasNoErrors();
        $producto = Producto::where('nombre', 'Café especial')->firstOrFail();

        $this->actingAs($empleado)->post(route('productos.store'), [
            'nombre' => 'X', 'precio' => '-3', 'categoria' => 'nada', 'stock' => '-1',
        ])->assertSessionHasErrors(['nombre', 'precio', 'categoria', 'stock']);

        $this->actingAs($empleado)->put(route('productos.update', $producto), [
            'nombre' => 'Café especial', 'precio' => '15', 'categoria' => $producto->categoria, 'disponible' => '1', 'stock' => '30',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(15, $producto->fresh()->precio);
        $this->assertSame(30, $producto->fresh()->stock);

        $antes = $producto->fresh()->disponible;
        $this->actingAs($empleado)->post(route('productos.alternar', $producto))->assertRedirect();
        $this->assertNotSame($antes, $producto->fresh()->disponible);

        $this->actingAs($empleado)->delete(route('productos.destroy', $producto))->assertRedirect();
        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    public function test_producto_agotado_no_se_puede_pedir(): void
    {
        $cliente = $this->usuario();
        $producto = Producto::first();
        $producto->update(['disponible' => false]);
        $this->actingAs($cliente)->post(route('pedido.agregar', $producto))->assertSessionHas('error');
        $this->assertSame(0, Pedido::count());
    }

    /* ------------------------------ Pago con QR ------------------------------ */

    public function test_la_pantalla_del_qr_muestra_un_qr_con_enlace_publico(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');

        $resp = $this->actingAs($cliente)->get(route('pago.show', $pago))->assertOk();
        $resp->assertSee('data:image/svg+xml;base64', false);
        $resp->assertSee('/pagar/'.$pago->referencia, false);
    }

    public function test_la_pagina_publica_del_qr_funciona_sin_iniciar_sesion(): void
    {
        [, $pago] = $this->pedidoHastaPago('qr');
        auth()->logout();

        $this->get(route('pagar.escanear', $pago->referencia))->assertOk();
        $this->post(route('pagar.ejecutar', $pago->referencia))->assertRedirect();
        // Sin código de 6 dígitos: el pago queda completado y el pedido pagado al instante.
        $this->assertSame('completado', $pago->fresh()->estado);
        $pedido = $pago->pedido->fresh();
        $this->assertSame(Pedido::PAGADO, $pedido->estado);
        $this->get(route('pagar.escanear', $pago->referencia))->assertOk()->assertSee($pedido->codigo);
        $this->get('/pagar/NOEXISTE')->assertNotFound();
    }

    public function test_flujo_completo_qr_codigo_factura_preparacion_y_listo(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $pedido = $pago->pedido;

        // Mientras espera, la pantalla consulta el estado; al pagar, lo lleva solo al código de recojo.
        $this->actingAs($cliente)->getJson(route('pago.estado', $pago))->assertJson(['siguiente' => null]);
        $this->actingAs($cliente)->post(route('pago.simular', $pago))->assertRedirect(route('pedido.exito', $pedido));
        $this->actingAs($cliente)->getJson(route('pago.estado', $pago))->assertJson(['siguiente' => route('pedido.exito', $pedido)]);

        $pedido = $pedido->fresh();
        $this->assertSame(Pedido::PAGADO, $pedido->estado);
        $this->assertNotEmpty($pedido->codigo);
        $this->assertNotNull($pedido->factura);
        $this->actingAs($cliente)->get(route('pedido.exito', $pedido))->assertOk()->assertSee($pedido->codigo)
            ->assertSee('Pedido #'.$pedido->id)->assertSee('Pagado')->assertSee('Bs '.number_format($pedido->total, 2));
        $this->actingAs($cliente)->get(route('factura.show', $pedido))->assertOk();

        // Empleado: en preparación con tiempo -> correo al cliente
        $empleado = $this->usuario('empleado');
        $this->actingAs($empleado)->post(route('entregas.preparar', $pedido), ['minutos' => 0])->assertSessionHasErrors('minutos');
        $this->actingAs($empleado)->post(route('entregas.preparar', $pedido), ['minutos' => 15])->assertSessionHasNoErrors();
        $this->assertSame(Pedido::EN_PREPARACION, $pedido->fresh()->estado);
        $this->assertSame(15, (int) $pedido->fresh()->tiempo_estimado);
        Mail::assertSent(CafeQrMail::class, fn ($m) => $m->hasTo($cliente->email) && str_contains($m->asunto, 'en preparación') && ($m->datos['minutos'] ?? null) == 15);

        // El cliente ve el tiempo en el seguimiento
        $this->actingAs($cliente)->get(route('pedido.seguimiento', $pedido))->assertOk()->assertSee('15');
        $this->actingAs($cliente)->get(route('pedido.estado', $pedido))->assertOk()->assertJsonFragment(['estado' => Pedido::EN_PREPARACION]);

        // Listo -> correo
        $this->actingAs($empleado)->post(route('entregas.listo', $pedido))->assertSessionHasNoErrors();
        $this->assertSame(Pedido::LISTO, $pedido->fresh()->estado);
        Mail::assertSent(CafeQrMail::class, fn ($m) => $m->hasTo($cliente->email) && str_contains($m->asunto, 'listo'));

        // Entrega
        $this->actingAs($empleado)->post(route('entregas.entregar', $pedido))->assertSessionHasNoErrors();
        $this->assertSame(Pedido::ENTREGADO, $pedido->fresh()->estado);

        // El admin ve la factura
        $this->actingAs($this->admin())->get(route('admin.facturas'))->assertOk()->assertSee('Juan Perez');
    }

    public function test_un_pago_no_se_acredita_dos_veces(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $pedido = $pago->pedido;

        $this->actingAs($cliente)->post(route('pago.simular', $pago))->assertRedirect(route('pedido.exito', $pedido));
        // Segundo intento (doble clic, o el celular confirma después): no hace nada.
        $this->actingAs($cliente)->post(route('pago.simular', $pago))->assertRedirect(route('pedido.exito', $pedido));
        auth()->logout();
        $this->post(route('pagar.ejecutar', $pago->referencia))->assertRedirect();

        $this->assertSame(1, Pago::where('pedido_id', $pedido->id)->where('estado', 'completado')->count());
        $this->assertSame(1, \App\Models\Factura::where('pedido_id', $pedido->id)->count());
        $this->assertSame(1, $this->correosDe('emails.pago-confirmado')->count());
        $this->assertSame('simulado', $pago->fresh()->confirmado_por);
        $this->assertNotEmpty($pago->fresh()->transaccion);
    }

    public function test_misma_transaccion_no_acredita_dos_pedidos_en_caja(): void
    {
        config(['cafeqr.pago_modo' => 'banco']);
        [$cliente1, $pago1] = $this->pedidoHastaPago('qr');
        [$cliente2, $pago2] = $this->pedidoHastaPago('qr');
        $this->actingAs($cliente1)->post(route('pago.ya-pague', $pago1));
        $this->actingAs($cliente2)->post(route('pago.ya-pague', $pago2));
        $this->assertSame('por_verificar', $pago1->fresh()->estado);

        $empleado = $this->usuario('empleado');
        $this->actingAs($empleado)->get(route('caja.pagos'))->assertOk()->assertSee('name="transaccion"', false)->assertSee('Pagos con QR por verificar');
        $this->actingAs($empleado)->post(route('caja.recibido', $pago1), [])->assertSessionHasErrors('transaccion');
        $this->actingAs($empleado)->post(route('caja.recibido', $pago1), ['transaccion' => 'bnb 778899'])->assertSessionHas('exito');
        $this->assertSame(Pedido::PAGADO, $pago1->pedido->fresh()->estado);
        $this->assertSame('BNB778899', $pago1->fresh()->transaccion);

        $this->actingAs($empleado)->post(route('caja.recibido', $pago2), ['transaccion' => 'BNB778899'])->assertSessionHas('error');
        $this->assertSame(Pedido::PENDIENTE_PAGO, $pago2->pedido->fresh()->estado);
        $this->assertSame('por_verificar', $pago2->fresh()->estado);

        // El mismo pago confirmado otra vez tampoco se acredita de nuevo.
        $this->actingAs($empleado)->post(route('caja.recibido', $pago1), ['transaccion' => 'OTRA123'])->assertSessionHas('error');
        $this->assertSame(1, \App\Models\Factura::where('pedido_id', $pago1->pedido_id)->count());

        // El cliente ya ve su código de recojo.
        $this->actingAs($cliente1)->getJson(route('pago.estado', $pago1))->assertJson(['siguiente' => route('pedido.exito', $pago1->pedido)]);
        $this->actingAs($empleado)->get(route('caja.pagos'))->assertOk()->assertSee('BNB778899');
    }

    public function test_cancelar_con_pago_pendiente(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $pedido = $pago->pedido;

        $this->actingAs($cliente)->post(route('pedido.cancelar', $pedido))->assertRedirect();
        $this->assertSame(Pedido::CANCELADO, $pedido->fresh()->estado);
        $this->assertSame('cancelado', $pago->fresh()->estado);
        $this->assertFalse((bool) $pedido->fresh()->reembolso_pendiente);
    }

    public function test_cancelar_despues_de_pagar_deja_reembolso_pendiente(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('qr');
        $this->actingAs($cliente)->post(route('pago.simular', $pago));
        $pedido = $pago->pedido->fresh();
        $this->assertSame(Pedido::PAGADO, $pedido->estado);

        $this->actingAs($cliente)->post(route('pedido.cancelar', $pedido))->assertRedirect();
        $this->assertSame(Pedido::CANCELADO, $pedido->fresh()->estado);
        $this->assertTrue((bool) $pedido->fresh()->reembolso_pendiente);

        $empleado = $this->usuario('empleado');
        $this->actingAs($empleado)->get(route('entregas.index'))->assertOk();
        $this->actingAs($cliente)->get(route('pedidos.historial'))->assertSee('Reembolso pendiente');
        $this->actingAs($empleado)->post(route('entregas.reembolsar', $pedido))->assertSessionHasNoErrors();
        $this->assertFalse((bool) $pedido->fresh()->reembolso_pendiente);
        $this->assertNotNull($pedido->fresh()->reembolsado_en);
        $this->actingAs($cliente)->get(route('pedidos.historial'))->assertSee('Reembolso realizado');
        $this->assertSame(1, $this->correosDe('emails.reembolso')->count());
    }

    public function test_cliente_no_ve_pagos_ni_pedidos_de_otro(): void
    {
        [, $pago] = $this->pedidoHastaPago('qr');
        $otro = $this->usuario();
        $this->actingAs($otro)->get(route('pago.show', $pago))->assertForbidden();
        $this->actingAs($otro)->get(route('pedido.seguimiento', $pago->pedido))->assertForbidden();
        $this->actingAs($otro)->post(route('pedido.cancelar', $pago->pedido))->assertForbidden();
    }

    /* ------------------------------ Efectivo ------------------------------ */

    public function test_flujo_efectivo_lo_confirma_el_empleado_en_caja(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('efectivo');
        $pedido = $pago->pedido;

        $this->actingAs($cliente)->get(route('pago.show', $pago))->assertOk();

        $empleado = $this->usuario('empleado');
        $this->actingAs($empleado)->get(route('caja.pagos'))->assertOk();
        $this->actingAs($empleado)->post(route('caja.recibido', $pago))->assertSessionHasNoErrors();

        $pedido = $pedido->fresh();
        $this->assertSame(Pedido::PAGADO, $pedido->estado);
        $this->assertNotNull($pedido->factura);
        $this->assertNotEmpty($pedido->codigo);
        Mail::assertSent(CafeQrMail::class, fn ($m) => $m->hasTo($cliente->email));
    }

    public function test_cliente_puede_modificar_pedido_pendiente(): void
    {
        [$cliente, $pago] = $this->pedidoHastaPago('efectivo');
        $this->actingAs($cliente)->post(route('pedido.modificar'))->assertRedirect(route('pedido.actual'));
        $this->assertSame(Pedido::CARRITO, $pago->pedido->fresh()->estado);
    }

    /* ------------------------------ Facturación ------------------------------ */

    public function test_nit_y_razon_social_se_validan(): void
    {
        $cliente = $this->usuario();
        $this->actingAs($cliente)->post(route('pedido.agregar', Producto::first()));
        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'qr', 'nit_ci' => 'abc'])->assertSessionHasErrors('nit_ci');
        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'bitcoin'])->assertSessionHasErrors('metodo');
        $this->actingAs($cliente)->post(route('pedido.pagar'), [])->assertSessionHasErrors('metodo');
    }

    public function test_foto_suelta_en_public_productos_se_detecta_sola(): void
    {
        $producto = Producto::create(['nombre' => 'Café Fotogénico', 'descripcion' => 'x', 'precio' => 5, 'categoria' => 'Snacks', 'disponible' => true]);
        // Sin foto propia: usa la ilustración de su categoría
        $this->assertStringContainsString('productos/ilustraciones/categoria-snacks', $producto->imagen_url);
        $this->assertFalse($producto->tiene_foto);

        $ruta = public_path('productos/cafe-fotogenico.png');
        @mkdir(dirname($ruta), 0777, true);
        file_put_contents($ruta, 'x');
        try {
            $this->assertStringEndsWith('productos/cafe-fotogenico.png', $producto->fresh()->imagen_url);
            $this->assertTrue($producto->fresh()->tiene_foto);
        } finally {
            @unlink($ruta);
        }
    }

    /* ------------------------------ Cupones (aporte de main) ------------------------------ */

    public function test_cupon_valido_descuenta_y_se_ve_en_el_pago(): void
    {
        $cliente = $this->usuario();
        $producto = Producto::where('disponible', true)->where('precio', '>=', 8)->first();
        $this->actingAs($cliente)->post(route('pedido.agregar', $producto), ['cantidad' => 3]); // más de Bs 20
        $subtotal = 3 * (float) $producto->precio;

        $this->actingAs($cliente)->post(route('pedido.cupon'), ['cupon' => ' univalle10 '])->assertSessionHas('exito');
        $pedido = $cliente->pedidos()->latest('id')->first();
        $this->assertSame('UNIVALLE10', $pedido->cupon);
        $this->assertEqualsWithDelta($subtotal - 10, (float) $pedido->total, 0.001);

        $this->actingAs($cliente)->post(route('pedido.pagar'), ['metodo' => 'qr'])->assertRedirect();
        $this->assertEqualsWithDelta($subtotal - 10, (float) Pago::latest('id')->first()->monto, 0.001);
    }

    public function test_cupon_invalido_o_muy_grande_se_rechaza(): void
    {
        $cliente = $this->usuario();
        $barato = Producto::where('disponible', true)->orderBy('precio')->first(); // Bs 4 a 5
        $this->actingAs($cliente)->post(route('pedido.agregar', $barato));

        $this->actingAs($cliente)->post(route('pedido.cupon'), ['cupon' => 'NOEXISTE'])->assertSessionHas('error');
        $this->actingAs($cliente)->post(route('pedido.cupon'), ['cupon' => 'CAFEQR20'])->assertSessionHas('error');
        $this->actingAs($cliente)->post(route('pedido.cupon'), [])->assertSessionHasErrors('cupon');
        $this->assertNull($cliente->pedidos()->latest('id')->first()->cupon);
    }

    public function test_cupon_se_quita_solo_si_el_pedido_baja_del_monto(): void
    {
        $cliente = $this->usuario();
        $caro = Producto::where('disponible', true)->where('precio', '>=', 12)->first();
        $this->actingAs($cliente)->post(route('pedido.agregar', $caro), ['cantidad' => 1]);
        $this->actingAs($cliente)->post(route('pedido.agregar', $caro), ['cantidad' => 1]); // 2 unidades
        $this->actingAs($cliente)->post(route('pedido.cupon'), ['cupon' => 'UNIVALLE10']);
        $pedido = $cliente->pedidos()->latest('id')->first();
        $this->assertSame('UNIVALLE10', $pedido->fresh()->cupon);

        $detalle = $pedido->detalles()->first();
        $this->actingAs($cliente)->patch(route('pedido.cambiar', $detalle), ['cantidad' => 1]);
        $pedido->refresh();
        if ((float) $caro->precio <= 10) {
            $this->assertNull($pedido->cupon);
        } else {
            $this->assertEqualsWithDelta((float) $caro->precio - 10, (float) $pedido->total, 0.001);
        }
        $this->actingAs($cliente)->delete(route('pedido.cupon.quitar'));
        $this->assertNull($pedido->fresh()->cupon);
        $this->assertEqualsWithDelta((float) $caro->precio, (float) $pedido->fresh()->total, 0.001);
    }

    public function test_menu_muestra_la_categoria_combos(): void
    {
        $this->actingAs($this->usuario())->get(route('dashboard'))->assertOk()->assertSee('Combo ejecutivo')->assertSee('Combos');
    }

    /* ------------------------------ Correos (bienvenida, recuperar, historial) ------------------------------ */

    private function correosDe(string $vista)
    {
        return Mail::sent(CafeQrMail::class)->filter(fn ($m) => $m->vistaCorreo === $vista);
    }

    public function test_registro_envia_correo_de_bienvenida_y_lo_guarda(): void
    {
        $this->post(route('registro.guardar'), [
            'name' => 'Lucia Rios', 'email' => 'lucia@est.univalle.edu',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertSessionHasNoErrors()->assertSessionHas('exito');

        $enviados = $this->correosDe('emails.bienvenida');
        $this->assertCount(1, $enviados);
        $this->assertTrue($enviados->first()->hasTo('lucia@est.univalle.edu'));
        $this->assertDatabaseHas('correos', ['para' => 'lucia@est.univalle.edu', 'tipo' => 'bienvenida']);

        // Sin botón de confirmar: el correo no lleva enlaces y el perfil no pide confirmar nada
        $html = Correo::where('para', 'lucia@est.univalle.edu')->value('html');
        $this->assertStringNotContainsString('verificar', $html);
        $this->actingAs(User::where('email', 'lucia@est.univalle.edu')->first())->get(route('perfil.show'))
            ->assertOk()->assertDontSee('Sin confirmar')->assertDontSee('Reenviar correo');
    }

    public function test_recuperar_contrasena_envia_correo_cafeqr(): void
    {
        $cliente = $this->usuario('cliente', 'olvido@est.univalle.edu');
        $this->post(route('password.email'), ['email' => 'OLVIDO@est.univalle.edu'])->assertSessionHas('exito');

        $enviados = $this->correosDe('emails.recuperar');
        $this->assertCount(1, $enviados);
        $this->assertStringContainsString('/restablecer/', $enviados->first()->datos['url']);

        // Correo que no existe: misma respuesta y no se envía nada
        $this->post(route('password.email'), ['email' => 'nadie@est.univalle.edu'])->assertSessionHas('exito');
        $this->assertCount(1, $this->correosDe('emails.recuperar'));
    }

    public function test_si_gmail_falla_no_se_rompe_y_queda_registrado(): void
    {
        // Servidor de correo inexistente: el envío falla de verdad
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);
        app('mail.manager')->purge();

        // Registro: la cuenta se crea igual y avisa del problema
        $this->post(route('registro.guardar'), [
            'name' => 'Pedro Paz', 'email' => 'pedro@est.univalle.edu',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        ])->assertRedirect(route('dashboard'))->assertSessionHas('error');
        $this->assertAuthenticated();

        $fallido = Correo::where('para', 'pedro@est.univalle.edu')->firstOrFail();
        $this->assertSame('fallido', $fallido->estado);
        $this->assertSame(2, $fallido->intentos);   // se reintentó
        $this->assertNotEmpty($fallido->html);

        // Recuperar contraseña: tampoco da error 500
        auth()->logout();
        $this->post(route('password.email'), ['email' => 'pedro@est.univalle.edu'])->assertRedirect()->assertSessionHas('error');

        // El admin lo ve y lo puede reenviar (vuelve a fallar sin romperse)
        $this->actingAs($this->admin())->get(route('admin.correos', ['estado' => 'fallido']))->assertOk()->assertSee('pedro@est.univalle.edu');
        $this->actingAs($this->admin())->post(route('admin.correos.reenviar', $fallido))->assertSessionHas('error');
    }

    public function test_admin_ve_correos_y_reenvia(): void
    {
        $cliente = $this->usuario();
        Notificador::bienvenida($cliente);
        $correo = Correo::latest('id')->firstOrFail();
        $correo->update(['estado' => 'fallido', 'error' => 'prueba']);

        $this->actingAs($this->admin())->get(route('admin.panel'))->assertOk()->assertSee('Correos que no se enviaron');
        $this->actingAs($this->admin())->get(route('admin.correos'))->assertOk()->assertSee($cliente->email);
        $this->actingAs($this->admin())->post(route('admin.correos.reenviar', $correo))->assertSessionHas('exito');
        $this->assertNotSame('fallido', $correo->fresh()->estado);

        $this->actingAs($cliente)->get(route('admin.correos'))->assertRedirect();
    }

    public function test_admin_nuevo_admi7_y_reemplaza_al_viejo(): void
    {
        $this->assertSame('admi7@est.univalle.edu', config('cafeqr.admin.email'));
        $this->post(route('login.entrar'), ['email' => 'admi7@est.univalle.edu', 'password' => 'Admi1234@'])->assertRedirect(route('admin.panel'));
        auth()->logout();

        // Si existía un admin con el correo viejo, se actualiza ese mismo (no queda duplicado)
        $this->admin()->update(['email' => 'viejo@est.univalle.edu', 'password' => 'Vieja12345']);
        (new \Database\Seeders\AdminSeeder)->run();
        $this->assertSame(1, User::where('rol', 'administrador')->count());
        $this->assertSame('admi7@est.univalle.edu', $this->admin()->email);
        $this->assertDatabaseMissing('users', ['email' => 'viejo@est.univalle.edu']);
        $this->post(route('login.entrar'), ['email' => 'admi7@est.univalle.edu', 'password' => 'Admi1234@']);
        $this->assertAuthenticatedAs($this->admin());
    }
}
