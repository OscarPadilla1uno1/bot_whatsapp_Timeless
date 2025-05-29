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
        Schema::create('vehicle_job_assignments', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->unsignedBigInteger('user_id')->comment('ID del motorista/vehículo');
            $table->unsignedBigInteger('job_id')->comment('ID del pedido');
            
            // Control de asignaciones
            $table->string('assignment_hash')->comment('Hash de la asignación original para agrupar');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending')
                  ->comment('Estado del trabajo asignado');
            
            // Datos del trabajo
            $table->json('job_data')->comment('Datos completos del pedido/trabajo');
            $table->integer('route_order')->nullable()->comment('Orden en la ruta');
            
            // Timestamps de control
            $table->timestamp('assigned_at')->comment('Cuándo se asignó');
            $table->timestamp('started_at')->nullable()->comment('Cuándo se inició');
            $table->timestamp('completed_at')->nullable()->comment('Cuándo se completó');
            
            // Notas y observaciones
            $table->text('completion_notes')->nullable()->comment('Notas al completar');
            $table->text('admin_notes')->nullable()->comment('Notas del administrador');
            
            // Timestamps estándar
            $table->timestamps();
            
            // Índices para rendimiento
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['assignment_hash'], 'idx_assignment_hash');
            $table->index(['status', 'assigned_at'], 'idx_status_assigned');
            
            // Evitar duplicados en la misma asignación
            $table->unique(['user_id', 'job_id', 'assignment_hash'], 'unique_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_job_assignments');
    }
};