<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OPTIMIZACIÓN: Índices para acelerar queries del dashboard
     * Estos índices mejoran significativamente el rendimiento de:
     * - enrichWithShipments(): Búsqueda por invoice_number
     * - enrichWithAudits(): Búsqueda por admission_number
     * - enrichWithAdmissionsLists(): Búsqueda por period + admission_number
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Índice compuesto para búsqueda por invoice_number y filtro por verified_shipment_date
            $table->index(['invoice_number', 'verified_shipment_date'], 'idx_shipments_invoice_verified');
        });

        Schema::table('audits', function (Blueprint $table) {
            // Índice simple para búsqueda por admission_number
            $table->index('admission_number', 'idx_audits_admission');
        });

        Schema::table('admissions_lists', function (Blueprint $table) {
            // Índice compuesto para búsqueda por period y admission_number
            $table->index(['period', 'admission_number'], 'idx_admissions_lists_period_admission');

            // Índice para búsqueda por admission_number (usado en otros contextos)
            $table->index('admission_number', 'idx_admissions_lists_admission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('idx_shipments_invoice_verified');
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('idx_audits_admission');
        });

        Schema::table('admissions_lists', function (Blueprint $table) {
            $table->dropIndex('idx_admissions_lists_period_admission');
            $table->dropIndex('idx_admissions_lists_admission');
        });
    }
};
