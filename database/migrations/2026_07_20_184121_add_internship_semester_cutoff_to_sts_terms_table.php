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
        Schema::table('sts_terms', function (Blueprint $table) {
            // Within internship_level_cutoff itself, which semester (1 or 2) placements start
            // counting as Internship rather than STS - e.g. level_cutoff=300, semester_cutoff=2
            // means "Level 300, Second Semester onward (and every level above) is Internship".
            $table->unsignedTinyInteger('internship_semester_cutoff')->default(2)->after('internship_level_cutoff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sts_terms', function (Blueprint $table) {
            $table->dropColumn('internship_semester_cutoff');
        });
    }
};
