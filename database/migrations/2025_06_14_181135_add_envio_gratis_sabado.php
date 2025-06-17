<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('envios_gratis_fechas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique(); // clave única
            $table->boolean('activo')->default(false); // por defecto no hay envío gratis
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
