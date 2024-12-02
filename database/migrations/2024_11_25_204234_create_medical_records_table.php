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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('patient')->nullable();
            $table->string('color')->nullable();
            $table->string('description')->nullable();
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
        Schema::dropIfExists('medical_records');
        Schema::enableForeignKeyConstraints();
    }
};