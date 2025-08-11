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
        Schema::table('medical_record_requests', function (Blueprint $table) {
            $table->string('derived_by')->nullable()->default(null)->after('requested_nick');
            $table->timestamp('derived_at')->nullable()->default(null)->after('derived_by');
            
            // Agregar foreign key constraint
            $table->foreign('derived_by')->references('nick')->on('users')->onDelete('set null');
        });
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