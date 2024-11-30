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
            $table->integer('number')->unique();
            $table->dateTime('attendance_date');
            $table->string('type')->nullable();
            $table->string('doctor')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->foreignId('insurer_id')->constrained('insurers')->nullable();
            $table->string('company')->nullable();
            $table->enum('status', ['Pendiente', 'Liquidado', 'Anulado'])->nullable();
            $table->string('patient')->nullable();
            $table->foreignId('medical_record_id')->constrained('medical_records')->nullable();
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