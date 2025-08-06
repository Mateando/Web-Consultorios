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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('document_type', ['cedula', 'pasaporte', 'tarjeta_identidad', 'registro_civil'])
                  ->default('cedula')
                  ->after('identification_number');
            
            // Renombrar identification_number a document_number
            $table->renameColumn('identification_number', 'document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('document_type');
            $table->renameColumn('document_number', 'identification_number');
        });
    }
};
