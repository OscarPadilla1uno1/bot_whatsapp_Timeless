<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('origin_lat', 20);
            $table->string('origin_lng', 20);
            $table->string('destination_lat', 20);
            $table->string('destination_lng', 20);
            $table->string('origin_address')->nullable();
            $table->string('destination_address')->nullable();
            $table->json('route_data')->nullable(); // Para guardar la ruta completa si es necesario
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_routes');
    }
};