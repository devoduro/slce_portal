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
     * Attempt to place a student at a partner school, enforcing category match, STS/Internship
     * type match, and first-come-first-serve quota under row locks so two concurrent requests
     * for the last open slot at a school cannot both succeed.
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

            // Only enforced once the school has a type set - older schools predating this
            // feature are left unrestricted until an admin classifies them.
            if ($locked->type && $locked->type !== $placement->type) {
                throw ValidationException::withMessages(['school' => 'This school is designated for ' . ($locked->type === 'internship' ? 'Internship' : 'STS') . ' placements, not ' . ($placement->type === 'internship' ? 'Internship' : 'STS') . '.']);
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

    /**
     * Admin override: assign or change a placement's partner school directly, bypassing the
     * "already selected" guard (unlike selectSchool()) so an admin can correct a wrong pick.
     * Category match and quota are still enforced so the assignment stays valid.
     */
    public static function adminAssignSchool(StsPlacement $placement, PartnerSchool $school): StsPlacement
    {
        return DB::transaction(function () use ($placement, $school) {
            $placement = StsPlacement::lockForUpdate()->findOrFail($placement->id);
            $locked = PartnerSchool::lockForUpdate()->findOrFail($school->id);

            if ($locked->category !== $placement->student->programme->sts_category) {
                throw ValidationException::withMessages(['school' => "This school is not available to the student's category."]);
            }

            if ($locked->type && $locked->type !== $placement->type) {
                throw ValidationException::withMessages(['school' => "This school is designated for " . ($locked->type === 'internship' ? 'Internship' : 'STS') . " placements, not " . ($placement->type === 'internship' ? 'Internship' : 'STS') . "."]);
            }

            $filled = StsPlacement::where('partner_school_id', $locked->id)
                ->where('sts_term_id', $placement->sts_term_id)
                ->where('level', $placement->level)
                ->where('id', '!=', $placement->id)
                ->count();

            if ($filled >= $locked->capacityForLevel($placement->level)) {
                throw ValidationException::withMessages(['school' => "{$locked->name}'s quota for level {$placement->level} is full."]);
            }

            $placement->update([
                'partner_school_id' => $locked->id,
                'selected_at' => now(),
            ]);

            return $placement;
        });
    }

    /**
     * Admin override: clear a placement's school selection (e.g. it was picked in error, or the
     * student needs to be freed up to choose again), freeing the quota slot it held.
     */
    public static function undoSchoolSelection(StsPlacement $placement): void
    {
        $placement->update([
            'partner_school_id' => null,
            'selected_at' => null,
        ]);
    }
}
