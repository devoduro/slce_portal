<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_lecturer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained('lecturers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'lecturer_id']);
        });

        // Preserve every course's existing single lecturer assignment as the first pivot row.
        $now = now();
        $rows = DB::table('courses')->whereNotNull('lecturer_id')->get(['id', 'lecturer_id']);

        foreach ($rows as $row) {
            DB::table('course_lecturer')->insert([
                'course_id' => $row->id,
                'lecturer_id' => $row->lecturer_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
            $table->dropColumn('lecturer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('lecturer_id')->nullable()->after('is_core')->constrained('lecturers')->nullOnDelete();
        });

        // Restore the first pivot assignment per course as the single lecturer_id.
        $firstAssignments = DB::table('course_lecturer')
            ->select('course_id', DB::raw('MIN(lecturer_id) as lecturer_id'))
            ->groupBy('course_id')
            ->get();

        foreach ($firstAssignments as $assignment) {
            DB::table('courses')->where('id', $assignment->course_id)->update(['lecturer_id' => $assignment->lecturer_id]);
        }

        Schema::dropIfExists('course_lecturer');
    }
};
