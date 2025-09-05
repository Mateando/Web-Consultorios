<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pivote doctor <-> study_types
        if (!Schema::hasTable('doctor_study_type')) {
            Schema::create('doctor_study_type', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
                $table->foreignId('study_type_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['doctor_id','study_type_id']);
            });
        }

        // Añadir columna opcional study_type_id a appointments si no existe
        if (!Schema::hasColumn('appointments','study_type_id')) {
            Schema::table('appointments', function(Blueprint $table){
                $table->foreignId('study_type_id')->nullable()->after('doctor_id')->constrained('study_types')->nullOnDelete();
            });
        }

        // Asegurar specialty_id (si se va a usar coherencia) - solo si no existe
        if (!Schema::hasColumn('appointments','specialty_id')) {
            Schema::table('appointments', function(Blueprint $table){
                $table->foreignId('specialty_id')->nullable()->after('study_type_id')->constrained('specialties')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments','specialty_id')) {
            Schema::table('appointments', function(Blueprint $table){
                $table->dropForeign(['specialty_id']);
                $table->dropColumn('specialty_id');
            });
        }
        if (Schema::hasColumn('appointments','study_type_id')) {
            Schema::table('appointments', function(Blueprint $table){
                $table->dropForeign(['study_type_id']);
                $table->dropColumn('study_type_id');
            });
        }
        Schema::dropIfExists('doctor_study_type');
    }
};
