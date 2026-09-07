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
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('graduated_academic_year_id')->nullable()->after('status')
                ->constrained('academic_years')->nullOnDelete();
        });

        // Backfill students already marked graduated before this column existed (e.g. via the
        // Promotions tool), so they show the academic year they actually graduated in rather than
        // nothing. Best-effort: use whichever academic year was in progress when their status
        // changed (updated_at).
        $graduated = DB::table('students')->where('status', 'graduated')->whereNull('graduated_academic_year_id')->get(['id', 'updated_at']);

        foreach ($graduated as $student) {
            $academicYear = DB::table('academic_years')
                ->where('start_date', '<=', $student->updated_at)
                ->orderByDesc('start_date')
                ->first();

            if ($academicYear) {
                DB::table('students')->where('id', $student->id)->update(['graduated_academic_year_id' => $academicYear->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['graduated_academic_year_id']);
            $table->dropColumn('graduated_academic_year_id');
        });
    }
};
