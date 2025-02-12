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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number');
            $table->string('invoice_number')->nullable();
            $table->string('auditor');
            $table->string('description', 500)->nullable();
            $table->enum('type', ['Regular', 'Devolucion'])->default('Regular');
            $table->enum('status', ['Aprobado', 'Con Observaciones', 'Rechazado', 'Pendiente'])->default('Pendiente');
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
        Schema::dropIfExists('audits');
        Schema::enableForeignKeyConstraints();
    }
};