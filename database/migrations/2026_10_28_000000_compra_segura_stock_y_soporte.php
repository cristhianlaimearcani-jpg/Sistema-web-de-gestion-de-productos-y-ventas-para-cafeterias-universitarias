<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - productos.stock: cuántas unidades hay de cada producto.
 * - pedidos.clave_intento: clave única de cada intento de compra (evita pedidos duplicados).
 * - pedidos.stock_reservado: si ya se descontó el stock (para devolverlo una sola vez al cancelar).
 * - pagos.transaccion: referencia de la transacción; única, así un mismo pago no se acredita dos veces.
 * - tickets y ticket_mensajes: soporte para clientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'stock')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->unsignedInteger('stock')->default(20);
            });
        }

        if (! Schema::hasColumn('pedidos', 'clave_intento')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->string('clave_intento', 64)->nullable()->unique();
                $table->boolean('stock_reservado')->default(false);
            });
        }

        if (! Schema::hasColumn('pagos', 'transaccion')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->string('transaccion', 60)->nullable()->unique();
                $table->timestamp('confirmado_en')->nullable();
                $table->string('confirmado_por', 20)->nullable();   // celular | simulado | caja
            });
        }

        if (! Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pedido_id')->nullable()->constrained()->nullOnDelete();
                $table->string('tipo', 20);                     // pago | pedido | error | cuenta | otro
                $table->string('asunto', 120);
                $table->string('estado', 20)->default('abierto')->index();   // abierto | en_proceso | resuelto
                $table->timestamp('resuelto_en')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ticket_mensajes')) {
            Schema::create('ticket_mensajes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->boolean('es_personal')->default(false);
                $table->text('mensaje');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_mensajes');
        Schema::dropIfExists('tickets');
        Schema::table('pagos', fn (Blueprint $t) => $t->dropColumn(['transaccion', 'confirmado_en', 'confirmado_por']));
        Schema::table('pedidos', fn (Blueprint $t) => $t->dropColumn(['clave_intento', 'stock_reservado']));
        Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('stock'));
    }
};
