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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions');
            $table->string('biller')->nullable(); // Nombre del facturador
            $table->boolean('received_file')->default(false);
            $table->dateTime('reception_date')->nullable();
            $table->boolean('settled')->default(false);
            $table->dateTime('settled_date')->nullable();
            $table->boolean('audited')->default(false);
            $table->dateTime('audited_date')->nullable();
            $table->boolean('billed')->default(false);
            $table->foreignId('invoice_id')->constrained('invoices')->nullable();
            $table->boolean('shipped')->default(false);
            $table->dateTime('shipment_date')->nullable();
            $table->boolean('paid')->default(false);
            $table->dateTime('payment_date')->nullable();
            $table->enum('status', ['Pendiente', 'Liquidado', 'Auditoría', 'Facturado', 'Enviado', 'Pagado'])->default('Pendiente');
            $table->string('period')->nullable();
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
        Schema::dropIfExists('settlements');
        Schema::enableForeignKeyConstraints();
    }
};