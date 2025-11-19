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
        // Verificar si las columnas ya existen antes de agregarlas
        if (!Schema::hasColumn('medical_record_requests', 'derived_by')) {
            Schema::table('medical_record_requests', function (Blueprint $table) {
                $table->string('derived_by')->nullable()->default(null)->after('requested_nick');
            });
        }

        if (!Schema::hasColumn('medical_record_requests', 'derived_at')) {
            Schema::table('medical_record_requests', function (Blueprint $table) {
                $table->timestamp('derived_at')->nullable()->default(null)->after('derived_by');
            });
        }

        // Verificar si el foreign key ya existe
        try {
            $foreignKeyExists = \DB::select(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'medical_record_requests'
                 AND CONSTRAINT_NAME = 'medical_record_requests_derived_by_foreign'"
            );

            if (empty($foreignKeyExists)) {
                Schema::table('medical_record_requests', function (Blueprint $table) {
                    $table->foreign('derived_by')->references('nick')->on('users')->onDelete('set null');
                });
            }
        } catch (\Exception $e) {
            // Si ya existe el foreign key, ignorar el error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_record_requests', function (Blueprint $table) {
            $table->dropForeign(['derived_by']);
            $table->dropColumn(['derived_by', 'derived_at']);
        });
    }
};