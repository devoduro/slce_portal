<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\PartnerSchool;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use App\Services\StsPlacementService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StsPlacementController extends Controller
{
    /**
     * Display a listing of placements for the current STS term.
     */
    public function index(Request $request)
    {
        $term = StsTerm::where('is_current', true)->first();

        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [20, 50, 100, 200, 500], true)) {
            $perPage = 50;
        }

        $placements = collect();

        // Each row renders two lecturer <select> lists and a school <select> list, so at the
        // full placement count (1000+ per term) an unpaginated page balloons to tens of MB -
        // paginate rather than ->get() the whole term's placements.
        if ($term) {
            $placements = StsPlacement::with(['student.programme', 'partnerSchool', 'lecturer', 'secondLecturer'])
                ->where('sts_term_id', $term->id)
                ->when($request->filled('category'), function ($q) use ($request) {
                    $q->whereHas('student.programme', fn ($q2) => $q2->where('sts_category', $request->category));
                })
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function ($q2) use ($search) {
                        $q2->where('students.index_number', 'like', "%{$search}%")
                            ->orWhere('students.full_name', 'like', "%{$search}%")
                            ->orWhere('students.reference_number', 'like', "%{$search}%");
                    });
                })
                ->join('students', 'students.id', '=', 'sts_placements.student_id')
                ->orderBy('students.full_name')
                ->select('sts_placements.*')
                ->paginate($perPage)
                ->withQueryString();
        }

        $lecturers = Lecturer::orderBy('name')->get();
        $partnerSchools = PartnerSchool::orderBy('name')->get();

        return view('sts-placements.index', compact('term', 'placements', 'lecturers', 'partnerSchools'));
    }

    /**
     * Assign the primary and/or second supervisor (lecturer) to a placement.
     */
    public function assignSupervisor(Request $request, StsPlacement $stsPlacement)
    {
        $request->validate([
            'lecturer_id' => 'nullable|exists:lecturers,id',
            'second_lecturer_id' => 'nullable|exists:lecturers,id|different:lecturer_id',
        ], [
            'second_lecturer_id.different' => 'The second supervisor must be a different lecturer from the primary one.',
        ]);

        $stsPlacement->update([
            'lecturer_id' => $request->lecturer_id ?: null,
            'second_lecturer_id' => $request->second_lecturer_id ?: null,
            'supervisor_assigned_at' => now(),
        ]);

        return back()->with('success', 'Supervisor(s) updated.');
    }

    /**
     * Admin override: clear a placement's partner school selection so the student can be
     * re-assigned or select again (e.g. the wrong school was picked).
     */
    public function undoSchool(StsPlacement $stsPlacement)
    {
        StsPlacementService::undoSchoolSelection($stsPlacement);

        return back()->with('success', "School selection undone for {$stsPlacement->student->full_name}. They can select again, or you can assign one directly.");
    }

    /**
     * Admin override: assign or change a placement's partner school directly, still enforcing
     * category match and quota so the assignment stays valid.
     */
    public function changeSchool(Request $request, StsPlacement $stsPlacement)
    {
        $request->validate(['partner_school_id' => 'required|exists:partner_schools,id']);

        try {
            StsPlacementService::adminAssignSchool($stsPlacement, PartnerSchool::findOrFail($request->partner_school_id));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', "School updated for {$stsPlacement->student->full_name}.");
    }
}
