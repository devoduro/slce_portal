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
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // First Class, Second Class Upper, etc.
            $table->decimal('min_cgpa', 3, 2); // Minimum CGPA for this classification
            $table->decimal('max_cgpa', 3, 2); // Maximum CGPA for this classification
            $table->unique('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classifications');
    }
};
