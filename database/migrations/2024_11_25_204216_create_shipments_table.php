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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->dateTime('verified_shipment_date')->nullable();
            $table->dateTime('reception_date')->nullable();
            $table->string('admission_number');
            $table->string('invoice_number');
            $table->text('remarks')->nullable();
            $table->dateTime('trama_date')->nullable();
            $table->dateTime('courier_date')->nullable();
            $table->dateTime('email_verified_date')->nullable();
            $table->string('url_sustenance')->nullable();
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
        Schema::dropIfExists('shipments');
        Schema::enableForeignKeyConstraints();
    }
};
