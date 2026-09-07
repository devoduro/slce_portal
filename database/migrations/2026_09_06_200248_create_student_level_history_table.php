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
        // Records what level a student actually held during a given academic year, snapshotted
        // at the moment they're promoted out of it. Without this, fee lookups have no way to
        // know a student's historical level - Student::applicableFeeStructure() would otherwise
        // fall back to the student's *current* level for every year, which silently rewrites a
        // past year's tuition fee to whatever the student's level happens to be today as soon as
        // they're promoted. See Student::levelForAcademicYear().
        Schema::create('student_level_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_level_history');
    }
};
