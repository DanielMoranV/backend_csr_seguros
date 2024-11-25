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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions');
            $table->string('biller');
            $table->boolean('received_file');
            $table->dateTime('reception_date');
            $table->boolean('settled');
            $table->dateTime('settled_date');
            $table->boolean('audited');
            $table->dateTime('audited_date');
            $table->boolean('billed');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->boolean('shipped');
            $table->dateTime('shipment_date');
            $table->boolean('paid');
            $table->dateTime('payment_date');
            $table->enum('status', ['Pendiente', 'Liquidado', 'Auditoría', 'Facturado', 'Enviado', 'Pagado']);
            $table->string('period');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};