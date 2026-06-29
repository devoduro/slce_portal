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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('semester_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('grade'); // A, B+, B, C+, C, D+, D, E
            $table->decimal('grade_point', 3, 2); // 4.00, 3.50, etc.
            $table->decimal('score', 5, 2)->nullable(); // Actual score if available
            $table->string('remark')->nullable(); // Excellent, Very Good, etc.
            $table->boolean('is_repeated')->default(false);
            $table->unique(['student_id', 'course_id', 'semester_id', 'academic_year_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
