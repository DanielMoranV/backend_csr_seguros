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
            $table->dateTime('shipment_date');
            $table->dateTime('reception_date')->nullable();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->text('remarks')->nullable();
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
