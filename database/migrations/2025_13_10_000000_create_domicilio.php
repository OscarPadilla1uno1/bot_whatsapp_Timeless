<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->boolean('domicilio')->default(false)->after('longitud');
            $table->text('notas')
                ->nullable()
                ->after('domicilio');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('domicilio', 'notas');
        });
    }
};
