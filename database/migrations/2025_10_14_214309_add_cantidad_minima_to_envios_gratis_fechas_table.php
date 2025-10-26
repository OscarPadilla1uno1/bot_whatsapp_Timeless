<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('envios_gratis_fechas', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_minima')->default(3)->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envios_gratis_fechas', function (Blueprint $table) {
            $table->dropColumn('cantidad_minima');
        });
    }
};
