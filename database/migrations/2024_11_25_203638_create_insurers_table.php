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
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('shipping_period')->nullable(); // Periodo de envio en días
            $table->integer('payment_period')->nullable(); // Periodo de pago en días
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
        Schema::dropIfExists('insurers');
        Schema::enableForeignKeyConstraints();
    }
};