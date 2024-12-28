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
        Schema::create('medical_record_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requester_nick');
            $table->string('requested_nick')->nullable();
            $table->string('admission_number');
            $table->string('medical_record_number')->nullable();
            $table->dateTime('request_date');
            $table->dateTime('response_date')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', ['Atendido', 'Rechazado', 'Pendiente'])->default('Pendiente');
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
        Schema::dropIfExists('medical_record_requests');
        Schema::enableForeignKeyConstraints();
    }
};
