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
        Schema::create('admissions_lists', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number');
            $table->string('period');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('biller');
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->foreignId('audit_id')->nullable()->constrained('audits');
            $table->foreignId('medical_record_request_id')->nullable()->constrained('medical_record_requests');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions_lists');
    }
};
