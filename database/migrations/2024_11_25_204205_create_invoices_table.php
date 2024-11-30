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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->dateTime('issue_date');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['Pendiente', 'Pagado', 'NC'])->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->foreignId('admission_id')->constrained('admissions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};