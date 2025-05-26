<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Eliminar la tabla existente si existe
        Schema::dropIfExists('route_assignments');
        
        // Crear la tabla con la estructura correcta
        Schema::create('route_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->longText('route_data');
            $table->string('route_hash');
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            // Índices y claves foráneas
            $table->index('user_id');
            $table->index('route_hash');
            $table->unique('user_id');
            
            // Clave foránea (solo si la tabla users existe)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('route_assignments');
    }
};