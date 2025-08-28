<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('patient_type')->nullable()->after('user_id');
            $table->text('observations')->nullable()->after('weight');
            $table->text('extra_observations')->nullable()->after('observations');
            // Move insurance fields earlier logically if needed (already exist)
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['patient_type','observations','extra_observations']);
        });
    }
};
