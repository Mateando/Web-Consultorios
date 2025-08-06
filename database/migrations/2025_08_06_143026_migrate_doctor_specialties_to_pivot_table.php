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
        // Migrar datos existentes a la tabla pivot
        $doctors = DB::table('doctors')->whereNotNull('specialty_id')->get();
        
        foreach ($doctors as $doctor) {
            DB::table('doctor_specialty')->insert([
                'doctor_id' => $doctor->id,
                'specialty_id' => $doctor->specialty_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Eliminar la columna specialty_id de la tabla doctors
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['specialty_id']);
            $table->dropColumn('specialty_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar la columna specialty_id
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->constrained();
        });
        
        // Migrar datos de vuelta (solo la primera especialidad de cada doctor)
        $doctorSpecialties = DB::table('doctor_specialty')
            ->select('doctor_id', DB::raw('MIN(specialty_id) as specialty_id'))
            ->groupBy('doctor_id')
            ->get();
            
        foreach ($doctorSpecialties as $ds) {
            DB::table('doctors')
                ->where('id', $ds->doctor_id)
                ->update(['specialty_id' => $ds->specialty_id]);
        }
        
        // Limpiar la tabla pivot
        DB::table('doctor_specialty')->truncate();
    }
};
