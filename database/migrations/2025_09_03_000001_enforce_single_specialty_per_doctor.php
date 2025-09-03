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
        // 1) Keep a single pivot row per doctor (the one with smallest id)
        $keepIds = DB::table('doctor_specialty')
            ->select(DB::raw('MIN(id) as id'))
            ->groupBy('doctor_id')
            ->pluck('id')
            ->toArray();

        if (!empty($keepIds)) {
            DB::table('doctor_specialty')
                ->whereNotIn('id', $keepIds)
                ->delete();
        }

        // 2) Add a unique index on doctor_id to enforce single specialty per doctor
        Schema::table('doctor_specialty', function (Blueprint $table) {
            // use a deterministic index name so we can drop it in down()
            $table->unique('doctor_id', 'doctor_specialty_doctor_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_specialty', function (Blueprint $table) {
            $table->dropUnique('doctor_specialty_doctor_id_unique');
        });
    }
};
