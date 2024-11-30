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
        Schema::create('medical_record_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('requested_id')->constrained('users')->nullable();
            $table->dateTime('request_date');
            $table->dateTime('response_date')->nullable();
            $table->foreignId('medical_record_id')->constrained('medical_records');
            $table->string('remarks')->nullable();
            $table->enum('status', ['Atendido', 'Rechazado', 'Pendiente'])->default('Pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_record_requests');
    }
};