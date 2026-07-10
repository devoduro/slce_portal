<?php

namespace App\Http\Controllers;

use App\Models\PartnerSchool;
use App\Models\StsPlacement;
use App\Models\StsTerm;
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

        if ($term) {
            $placement = StsPlacement::with(['partnerSchool', 'lecturer'])
                ->where('student_id', $student->id)
                ->where('sts_term_id', $term->id)
                ->first();

            $eligible = $student->meetsRegistrationThreshold($term->semester);
            $percentage = $student->paymentPercentage($term->semester->academicYear);
        }

        return view('student.sts.index', compact('student', 'term', 'placement', 'eligible', 'percentage'));
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

        abort_unless($student->meetsRegistrationThreshold($term->semester), 403, 'You have not met the fee payment threshold required to select a school.');
        abort_if($placement->partner_school_id, 403, 'You have already selected a partner school.');

        $category = $student->programme->sts_category;

        $schools = PartnerSchool::where('category', $category)
            ->orderBy('name')
            ->get()
            ->map(fn ($school) => [
                'school' => $school,
                'available' => $school->availableQuota($placement->level, $term->id),
            ]);

        return view('student.sts.schools', compact('term', 'placement', 'schools'));
    }

    /**
     * Select a partner school (first-come-first-serve, quota-enforced).
     */
    public function select(Request $request, PartnerSchool $partnerSchool)
    {
        $student = Auth::user()->student;
        $term = StsTerm::where('is_current', true)->firstOrFail();

        abort_unless($student->meetsRegistrationThreshold($term->semester), 403, 'You have not met the fee payment threshold required to select a school.');

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
        $term = StsTerm::with('semester.academicYear')->where('is_current', true)->firstOrFail();

        $placement = StsPlacement::with(['partnerSchool', 'lecturer'])
            ->where('student_id', $student->id)
            ->where('sts_term_id', $term->id)
            ->firstOrFail();

        abort_unless($placement->partner_school_id && $placement->lecturer_id, 403, 'Your school and supervisor must both be assigned before you can print your letter.');
        abort_unless($student->meetsRegistrationThreshold($term->semester), 403, 'You have not met the fee payment threshold required to print your letter.');

        if (!$placement->letter_printed_at) {
            $placement->update(['letter_printed_at' => now()]);
        }

        $settings = DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();
        $stsSettings = DB::table('settings')->where('category', 'sts')->pluck('value', 'key')->toArray();

        $view = $placement->type === StsPlacement::TYPE_INTERNSHIP ? 'sts.letters.student-internship' : 'sts.letters.student-sts';

        return view($view, compact('student', 'placement', 'term', 'settings', 'stsSettings'));
    }
}
