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
        Schema::create('grade_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('grade'); // A, B+, B, C+, C, D+, D, E
            $table->decimal('min_score', 5, 2); // Minimum score for this grade
            $table->decimal('max_score', 5, 2); // Maximum score for this grade
            $table->decimal('grade_point', 3, 2); // 4.00, 3.50, etc.
            $table->string('remark'); // Excellent, Very Good, etc.
            $table->unique('grade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_schemes');
    }
};
