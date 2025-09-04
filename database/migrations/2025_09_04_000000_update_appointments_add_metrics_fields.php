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
        Schema::table('appointments', function (Blueprint $table) {
            // Nuevas columnas útiles para analíticas
            if (! Schema::hasColumn('appointments', 'specialty_id')) {
                $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete()->after('doctor_id');
            }

            if (! Schema::hasColumn('appointments', 'consultation_fee')) {
                $table->decimal('consultation_fee', 10, 2)->nullable()->after('duration');
            }

            if (! Schema::hasColumn('appointments', 'attended_at')) {
                $table->dateTime('attended_at')->nullable()->after('appointment_date');
            }

            if (! Schema::hasColumn('appointments', 'canceled_at')) {
                $table->dateTime('canceled_at')->nullable()->after('attended_at');
            }

            if (! Schema::hasColumn('appointments', 'source')) {
                $table->string('source')->nullable()->comment('origen: online, telefono, presencial')->after('status');
            }

            if (! Schema::hasColumn('appointments', 'room')) {
                $table->string('room')->nullable()->after('source');
            }

            // Índices para consultas analíticas y filtros comunes
            $table->index(['appointment_date'], 'idx_appointments_date');
            $table->index(['doctor_id', 'appointment_date'], 'idx_appointments_doctor_date');
            $table->index(['specialty_id'], 'idx_appointments_specialty');
            $table->index(['status'], 'idx_appointments_status');
            $table->index(['created_by'], 'idx_appointments_created_by');
            $table->index(['patient_id'], 'idx_appointments_patient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // eliminar índices si existen
            $table->dropIndex('idx_appointments_date');
            $table->dropIndex('idx_appointments_doctor_date');
            $table->dropIndex('idx_appointments_specialty');
            $table->dropIndex('idx_appointments_status');
            $table->dropIndex('idx_appointments_created_by');
            $table->dropIndex('idx_appointments_patient');

            // columnas añadidas
            if (Schema::hasColumn('appointments', 'specialty_id')) {
                // quitar FK y columna
                $table->dropForeign(['specialty_id']);
                $table->dropColumn('specialty_id');
            }

            if (Schema::hasColumn('appointments', 'consultation_fee')) {
                $table->dropColumn('consultation_fee');
            }

            if (Schema::hasColumn('appointments', 'attended_at')) {
                $table->dropColumn('attended_at');
            }

            if (Schema::hasColumn('appointments', 'canceled_at')) {
                $table->dropColumn('canceled_at');
            }

            if (Schema::hasColumn('appointments', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('appointments', 'room')) {
                $table->dropColumn('room');
            }
        });
    }
};
