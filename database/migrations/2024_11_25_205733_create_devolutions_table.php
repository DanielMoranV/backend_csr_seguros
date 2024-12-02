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
        Schema::create('devolutions', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->string('type');
            $table->text('reason');
            $table->string('period');
            $table->string('biller');
            $table->string('status');
            $table->foreignId('admission_id')->constrained('admissions');
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
        Schema::dropIfExists('devolutions');
        Schema::enableForeignKeyConstraints();
    }
};