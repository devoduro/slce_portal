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
        Schema::table('continuous_assessments', function (Blueprint $table) {
            $table->decimal('attendance_score', 5, 2)->nullable()->after('academic_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('continuous_assessments', function (Blueprint $table) {
            $table->dropColumn('attendance_score');
        });
    }
};
