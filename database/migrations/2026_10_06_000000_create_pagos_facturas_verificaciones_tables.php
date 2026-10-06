<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Datos de facturación y fecha de pago en el pedido
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('razon_social')->nullable();
            $table->string('nit_ci', 20)->nullable();
            $table->timestamp('pagado_en')->nullable();
        });

        // Cada intento de pago queda registrado.
        // Estados: pendiente -> por_verificar -> recibido -> pagado   (o fallido)
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained()->cascadeOnDelete();
            $table->decimal('monto', 8, 2);
            $table->string('metodo')->default('demo');       // demo | banco
            $table->string('estado')->default('pendiente');
            $table->string('referencia')->unique();
            $table->string('comprobante')->nullable();       // número de transacción que escribe el estudiante
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('recibido_en')->nullable();    // cuando el dinero llegó
            $table->timestamp('pagado_en')->nullable();      // cuando escribió el código del correo
            $table->string('detalle')->nullable();           // motivo si falló
            $table->timestamps();
        });

        // Códigos de 6 dígitos enviados por correo (se guardan encriptados)
        Schema::create('verificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnDelete();
            $table->string('codigo_hash');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_en');
            $table->timestamp('usado_en')->nullable();
            $table->timestamps();
        });

        // Factura emitida al confirmar el pago
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('numero')->unique();
            $table->foreignId('pedido_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('razon_social');
            $table->string('nit_ci', 20);
            $table->decimal('total', 8, 2);
            $table->string('codigo_verificacion');
            $table->timestamp('emitida_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('verificaciones');
        Schema::dropIfExists('pagos');

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['razon_social', 'nit_ci', 'pagado_en']);
        });
    }
};