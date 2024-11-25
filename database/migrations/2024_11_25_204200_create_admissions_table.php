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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->dateTime('attendance_date');
            $table->string('type');
            $table->string('doctor');
            $table->foreignId('insurer_id')->constrained('insurers');
            $table->string('company');
            $table->string('patient');
            $table->foreignId('medical_record_id')->constrained('medical_records');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};