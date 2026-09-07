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
        // Scopes an STS partner school to the term it was uploaded for, so each new academic
        // year starts with an empty STS list that the office uploads fresh. Internship schools
        // are deliberately left global (null term) - they carry over year to year.
        Schema::table('partner_schools', function (Blueprint $table) {
            $table->foreignId('sts_term_id')->nullable()->after('type')
                ->constrained('sts_terms')->nullOnDelete();
        });

        // Existing STS schools belong to the term they were actually used in, so last year's
        // placements keep resolving to a school that is still visible under that term.
        $termIdByStsSchool = DB::table('sts_placements')
            ->join('partner_schools', 'partner_schools.id', '=', 'sts_placements.partner_school_id')
            ->where('partner_schools.type', 'sts')
            ->select('partner_schools.id', DB::raw('MAX(sts_placements.sts_term_id) as term_id'))
            ->groupBy('partner_schools.id')
            ->pluck('term_id', 'id');

        foreach ($termIdByStsSchool as $schoolId => $termId) {
            DB::table('partner_schools')->where('id', $schoolId)->update(['sts_term_id' => $termId]);
        }

        // Any STS school never actually picked falls back to the most recent term where schools
        // were genuinely in use - not merely the latest term that has placement rows, which
        // would drop an unused school into a new year whose selection hasn't started yet.
        $fallbackTermId = DB::table('sts_placements')->whereNotNull('partner_school_id')->max('sts_term_id');

        if ($fallbackTermId) {
            DB::table('partner_schools')
                ->where('type', 'sts')
                ->whereNull('sts_term_id')
                ->update(['sts_term_id' => $fallbackTermId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_schools', function (Blueprint $table) {
            $table->dropForeign(['sts_term_id']);
            $table->dropColumn('sts_term_id');
        });
    }
};
