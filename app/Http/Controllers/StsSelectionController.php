<?php

namespace App\Http\Controllers;

use App\Models\PartnerSchool;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use App\Services\FeeLedgerService;
use App\Services\StsPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StsSelectionController extends Controller
{
    /**
     * Show the student's STS/Internship dashboard: eligibility, current placement, letter access.
     */
    public function index()
    {
        $student = Auth::user()->student;
        $term = StsTerm::with('semester.academicYear')->where('is_current', true)->first();

        $placement = null;
        $eligible = false;
        $percentage = 0;
        $isBiometricVerified = false;

        if ($term) {
            $placement = StsPlacement::with(['partnerSchool', 'lecturer'])
                ->where('student_id', $student->id)
                ->where('sts_term_id', $term->id)
                ->first();

            $isBiometricVerified = $student->hasBiometricVerification($term->semester);

            $eligible = $student->meetsRegistrationThreshold($term->semester);

            // Matches what meetsRegistrationThreshold() actually gates on (Total Balance Due vs
            // Bill Amount) rather than the raw un-netted tuition-paid percentage, so this figure
            // can't contradict the Threshold Met/Not Met badge shown right next to it.
            $billAmount = $student->tuitionFeeAmount($term->semester->academicYear);
            $balanceDue = FeeLedgerService::ledgerFor($student)[0]['balance'] ?? 0.0;
            $percentage = $billAmount > 0 ? round((($billAmount - $balanceDue) / $billAmount) * 100, 1) : 0.0;
        }

        // Every term the student has been placed in, so they can see where they've already been -
        // and understand why those schools are no longer offered to them.
        $placementHistory = StsPlacement::with(['partnerSchool', 'lecturer', 'stsTerm.semester.academicYear'])
            ->where('student_id', $student->id)
            ->when($term, fn ($q) => $q->where('sts_term_id', '!=', $term->id))
            ->whereNotNull('partner_school_id')
            ->get()
            ->sortByDesc(fn ($p) => $p->stsTerm->proposed_start_date ?? $p->created_at)
            ->values();

        // Drives whether the "Select a Partner School" button is offered at all - STS is First
        // Semester, Level 100-300 only (see StsPlacementService::stsSelectionAllowed()).
        $canSelectSchool = $term && $placement
            ? StsPlacementService::stsSelectionAllowed($student, $term, $placement)
            : true;

        return view('student.sts.index', compact('student', 'term', 'placement', 'eligible', 'percentage', 'isBiometricVerified', 'placementHistory', 'canSelectSchool'));
    }

    /**
     * List partner schools available to the student's category, with live remaining quota.
     */
    public function schools()
    {
        $student = Auth::user()->student;
        $term = StsTerm::where('is_current', true)->firstOrFail();

        $placement = StsPlacement::where('student_id', $student->id)
            ->where('sts_term_id', $term->id)
            ->firstOrFail();

        abort_unless($student->hasBiometricVerification($term->semester), 403, 'You must complete biometric check-in before you can select a school.');
        abort_unless($student->meetsRegistrationThreshold($term->semester), 403, 'You have not met the fee payment threshold required to select a school.');
        abort_if($placement->partner_school_id, 403, 'You have already selected a partner school.');

        if (!StsPlacementService::stsSelectionAllowed($student, $term, $placement)) {
            return redirect()->route('student.sts.index')
                ->with('error', 'STS is only available to Level 100-' . $term->internship_level_cutoff . ' students in the First Semester.');
        }

        $category = $student->programme->sts_category;

        // Schools this student has already attended in a previous term are left out entirely -
        // selectSchool() rejects them anyway, so offering them would only produce a dead end.
        $previousSchoolNames = StsPlacementService::previousSchoolNames($student, $term);

        $schools = PartnerSchool::where('category', $category)
            // Only schools designated for this placement's type (STS or Internship) - schools
            // predating that field (type is null) are left unrestricted.
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', $placement->type))
            // STS schools belong to the term they were uploaded for; internship schools are
            // global. Stops last year's STS list being offered before this year's is uploaded.
            ->usableInTerm($term->id)
            ->orderBy('name')
            ->get()
            ->reject(fn ($school) => $previousSchoolNames->contains(mb_strtolower(trim($school->name))))
            ->map(fn ($school) => [
                'school' => $school,
                'available' => $school->availableQuota($placement->level, $term->id),
            ])
            ->values();

        return view('student.sts.schools', compact('term', 'placement', 'schools', 'previousSchoolNames'));
    }

    /**
     * Select a partner school (first-come-first-serve, quota-enforced).
     */
    public function select(Request $request, PartnerSchool $partnerSchool)
    {
        $student = Auth::user()->student;
        $term = StsTerm::where('is_current', true)->firstOrFail();

        abort_unless($student->hasBiometricVerification($term->semester), 403, 'You must complete biometric check-in before you can select a school.');
        abort_unless($student->meetsRegistrationThreshold($term->semester), 403, 'You have not met the fee payment threshold required to select a school.');

        $placement = StsPlacement::where('student_id', $student->id)->where('sts_term_id', $term->id)->first();

        if ($placement && !StsPlacementService::stsSelectionAllowed($student, $term, $placement)) {
            return redirect()->route('student.sts.index')
                ->with('error', 'STS is only available to Level 100-' . $term->internship_level_cutoff . ' students in the First Semester.');
        }

        try {
            StsPlacementService::selectSchool($student, $term, $partnerSchool);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('student.sts.index')
            ->with('success', "You have been placed at {$partnerSchool->name}.");
    }

    /**
     * Print the student's placement letter (STS or Internship, depending on level).
     */
    public function printLetter()
    {
        $student = Auth::user()->student;
        $term = StsTerm::with('semester.academicYear')->where('is_current', true)->first();

        if (!$term) {
            return redirect()->route('student.sts.index')
                ->with('error', 'There is no active STS/Internship term right now.');
        }

        $placement = StsPlacement::with(['partnerSchool', 'lecturer'])
            ->where('student_id', $student->id)
            ->where('sts_term_id', $term->id)
            ->first();

        if (!$placement) {
            return redirect()->route('student.sts.index')
                ->with('error', 'You have not been included in this STS term yet.');
        }

        if (!$placement->partner_school_id || !$placement->lecturer_id) {
            return redirect()->route('student.sts.index')
                ->with('error', 'Your school and supervisor must both be assigned before you can print your letter.');
        }

        if (!$student->hasBiometricVerification($term->semester)) {
            return redirect()->route('student.sts.index')
                ->with('error', 'You must complete biometric check-in before you can print your letter.');
        }

        if (!$student->meetsRegistrationThreshold($term->semester)) {
            return redirect()->route('student.sts.index')
                ->with('error', 'You have not met the fee payment threshold required to print your letter.');
        }

        if (!$placement->letter_printed_at) {
            $placement->update(['letter_printed_at' => now()]);
        }

        $settings = DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();
        $stsSettings = DB::table('settings')->where('category', 'sts')->pluck('value', 'key')->toArray();

        $view = $placement->type === StsPlacement::TYPE_INTERNSHIP ? 'sts.letters.student-internship' : 'sts.letters.student-sts';

        return view($view, compact('student', 'placement', 'term', 'settings', 'stsSettings'));
    }
}
