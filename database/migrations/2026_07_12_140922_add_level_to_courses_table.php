<?php

use App\Models\Course;
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
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('level')->nullable()->after('credit_hours');
        });

        // Backfill from the course code's numbering convention (e.g. CEBC233 -> level 200):
        // the digit immediately after the letter prefix is the level's leading digit.
        Course::whereNull('level')->get(['id', 'code'])->each(function (Course $course) {
            if (preg_match('/^[A-Za-z]+(\d)\d{2}[A-Za-z]*$/', $course->code, $matches)) {
                $course->update(['level' => ((int) $matches[1]) * 100]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
