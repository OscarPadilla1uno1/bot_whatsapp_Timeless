<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('request_id')->nullable()->after('referencia_transaccion');
            $table->string('process_url', 255)->nullable()->after('request_id');
            $table->string('metodo_interno', 100)->nullable()->after('process_url'); // e.g., 'placetopay'
            $table->string('canal', 50)->nullable()->after('metodo_interno'); // e.g., 'web', 'whatsapp'
            $table->text('observaciones')->nullable()->after('canal');

            $table->index('request_id');
        });
    }

    public function down()
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropColumn([
                'request_id',
                'process_url',
                'metodo_interno',
                'canal',
                'observaciones',
            ]);
        });
    }
};
