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
        Schema::disableForeignKeyConstraints();
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->dateTime('attendance_date');
            $table->time('attendance_hour')->nullable();
            $table->string('type')->nullable();
            $table->string('doctor')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->foreignId('insurer_id')->nullable()->constrained('insurers');
            $table->string('company')->nullable();
            $table->enum('status', ['Pendiente', 'Liquidado', 'Pagado', 'Anulado'])->default('Pendiente');
            $table->string('patient')->nullable();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records');
            $table->foreignId('audit_id')->nullable()->constrained('audits');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('admissions');
        Schema::enableForeignKeyConstraints();
    }
};
