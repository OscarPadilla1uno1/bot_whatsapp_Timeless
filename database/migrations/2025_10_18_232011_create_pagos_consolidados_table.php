<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos_consolidados', function (Blueprint $table) {
            $table->id();

            // Relación con cliente
            $table->unsignedInteger('cliente_id');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');

            // Información del pago
            $table->decimal('monto_total', 10, 2)->default(0);
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia'])->default('tarjeta');
            $table->enum('estado_pago', ['pendiente', 'confirmado', 'fallido'])->default('pendiente');

            // Datos de PlacetoPay
            $table->string('referencia_transaccion')->nullable();
            $table->string('request_id')->nullable();
            $table->string('process_url')->nullable();

            // Información adicional
            $table->dateTime('fecha_pago')->nullable();
            $table->string('canal')->default('panel'); // panel, whatsapp, etc.
            $table->text('observaciones')->nullable();

            // Control de notificación al bot (para evitar duplicados)
            $table->boolean('notificado')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_consolidados');
    }
};
