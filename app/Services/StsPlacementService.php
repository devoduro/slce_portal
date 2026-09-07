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
     * Names of the schools a student has already been placed at in previous terms, lower-cased
     * and trimmed for comparison.
     *
     * Matched on NAME rather than partner_school_id on purpose: the school list is re-uploaded
     * each term, so the same physical school comes back as a brand new row with a new id. Only
     * the name is stable across those uploads.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function previousSchoolNames(Student $student, ?StsTerm $excludingTerm = null)
    {
        return StsPlacement::query()
            ->where('student_id', $student->id)
            ->when($excludingTerm, fn ($q) => $q->where('sts_term_id', '!=', $excludingTerm->id))
            ->whereNotNull('partner_school_id')
            ->with('partnerSchool:id,name')
            ->get()
            ->pluck('partnerSchool.name')
            ->filter()
            ->map(fn (string $name) => mb_strtolower(trim($name)))
            ->unique()
            ->values();
    }

    /**
     * Whether a placement is still within STS policy: STS runs in a First Semester term only,
     * and only up to the term's internship level cutoff (Level 100-300 by default). A student
     * above that does Internship instead and is never offered an STS school.
     *
     * Checked against the student's *current* level, not the level snapshotted on the placement,
     * so someone promoted after the term was generated can't act on a now-stale STS placement.
     */
    public static function stsSelectionAllowed(Student $student, StsTerm $term, StsPlacement $placement): bool
    {
        if ($placement->type !== StsPlacement::TYPE_STS) {
            return true;
        }

        if ((int) ($term->semester->semester_number ?? 1) !== 1) {
            return false;
        }

        return (int) ($student->level ?? 0) <= (int) $term->internship_level_cutoff;
    }

    /**
     * Attempt to place a student at a partner school, enforcing category match, STS/Internship
     * type match, no repeat of a school the student has already attended, and first-come-first-serve
     * quota under row locks so two concurrent requests for the last open slot at a school cannot
     * both succeed.
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

            // A student may not return to a school they have already been placed at. Compared by
            // name, since re-uploading the school list each term gives the same school a new id.
            if (self::previousSchoolNames($student, $term)->contains(mb_strtolower(trim($locked->name)))) {
                throw ValidationException::withMessages([
                    'school' => "You were previously placed at {$locked->name}. Please choose a school you have not attended before.",
                ]);
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
