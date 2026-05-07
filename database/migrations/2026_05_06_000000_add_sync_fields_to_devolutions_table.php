<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devolutions', function (Blueprint $table) {
            $table->string('sisclin_id')->nullable()->unique()->after('id');
            $table->string('admission_number')->nullable()->index()->after('admission_id');
            $table->string('medical_record_number')->nullable()->after('admission_number');
            $table->string('patient_name')->nullable()->after('medical_record_number');
            $table->string('insurer_name')->nullable()->after('patient_name');
            $table->dateTime('attendance_date')->nullable()->after('insurer_name');
            $table->string('doctor')->nullable()->after('attendance_date');
            $table->dateTime('invoice_date')->nullable()->after('doctor');
            $table->decimal('invoice_amount', 10, 2)->nullable()->after('invoice_date');
            $table->boolean('is_paid')->default(false)->after('status');
            $table->boolean('is_uncollectible')->default(false)->after('is_paid');
        });
    }

    public function down(): void
    {
        Schema::table('devolutions', function (Blueprint $table) {
            $table->dropUnique(['sisclin_id']);
            $table->dropIndex(['admission_number']);
            $table->dropColumn(['sisclin_id', 'admission_number', 'medical_record_number', 'patient_name', 'insurer_name', 'attendance_date', 'doctor', 'invoice_date', 'invoice_amount', 'is_paid', 'is_uncollectible']);
        });
    }
};
