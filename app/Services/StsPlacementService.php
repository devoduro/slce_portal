<?php

namespace App\Services;

use App\Models\PartnerSchool;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StsPlacementService
{
    /**
     * Attempt to place a student at a partner school, enforcing category match and
     * first-come-first-serve quota under row locks so two concurrent requests for
     * the last open slot at a school cannot both succeed.
     */
    public static function selectSchool(Student $student, StsTerm $term, PartnerSchool $school): StsPlacement
    {
        return DB::transaction(function () use ($student, $term, $school) {
            $placement = StsPlacement::where('student_id', $student->id)
                ->where('sts_term_id', $term->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($placement->partner_school_id) {
                throw ValidationException::withMessages(['school' => 'You have already selected a partner school.']);
            }

            $locked = PartnerSchool::where('id', $school->id)->lockForUpdate()->firstOrFail();

            if ($locked->category !== $student->programme->sts_category) {
                throw ValidationException::withMessages(['school' => 'This school is not available to your category.']);
            }

            $filled = StsPlacement::where('partner_school_id', $locked->id)
                ->where('sts_term_id', $term->id)
                ->where('level', $placement->level)
                ->count();

            if ($filled >= $locked->capacityForLevel($placement->level)) {
                throw ValidationException::withMessages(['school' => "This school's quota for your level is exhausted."]);
            }

            $placement->update([
                'partner_school_id' => $locked->id,
                'selected_at' => now(),
            ]);

            return $placement;
        });
    }
}
