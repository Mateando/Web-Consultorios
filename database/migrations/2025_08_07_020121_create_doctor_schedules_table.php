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
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('specialty_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('appointment_duration')->default(30); // Duración de cita en minutos
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['doctor_id', 'day_of_week', 'is_active']);
            $table->index(['doctor_id', 'specialty_id', 'day_of_week']);
            
            // Índice único para evitar duplicados
            $table->unique(['doctor_id', 'specialty_id', 'day_of_week', 'start_time', 'end_time'], 'doctor_schedule_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
