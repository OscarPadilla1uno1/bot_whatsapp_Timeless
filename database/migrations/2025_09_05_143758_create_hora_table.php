<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hora', function (Blueprint $table) {
            $table->id();
            $table->time('hora_inicio')->comment('Hora de inicio del bot');
            $table->time('hora_fin')->comment('Hora de fin del bot');
            $table->boolean('activo')->default(true)->comment('Si el horario está activo');
            $table->string('dias_semana')->default('1,2,3,4,5,6,7')->comment('Días de la semana (1=Lunes, 7=Domingo)');
            $table->timestamps();
        });

        // Insertar horario por defecto
        DB::table('hora')->insert([
            'hora_inicio' => '08:00:00',
            'hora_fin' => '22:00:00',
            'activo' => true,
            'dias_semana' => '1,2,3,4,5,6,7',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hora');
    }
};