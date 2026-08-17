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
        Schema::table('results', function (Blueprint $table) {
            // student_id's foreign key relies on the composite unique index below being
            // dropped next, so give it its own supporting index first.
            $table->index('student_id', 'results_student_id_index');

            $table->dropUnique(['student_id', 'course_id', 'semester_id', 'academic_year_id']);
            $table->unique(
                ['student_id', 'course_id', 'semester_id', 'academic_year_id', 'is_repeated'],
                'results_student_course_semester_year_repeated_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique('results_student_course_semester_year_repeated_unique');
            $table->unique(['student_id', 'course_id', 'semester_id', 'academic_year_id']);
            $table->dropIndex('results_student_id_index');
        });
    }
};
