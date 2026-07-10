<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use Illuminate\Http\Request;

class StsPlacementController extends Controller
{
    /**
     * Display a listing of placements for the current STS term.
     */
    public function index(Request $request)
    {
        $term = StsTerm::where('is_current', true)->first();

        $placements = collect();

        if ($term) {
            $placements = StsPlacement::with(['student.programme', 'partnerSchool', 'lecturer'])
                ->where('sts_term_id', $term->id)
                ->when($request->filled('category'), function ($q) use ($request) {
                    $q->whereHas('student.programme', fn ($q2) => $q2->where('sts_category', $request->category));
                })
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
                ->join('students', 'students.id', '=', 'sts_placements.student_id')
                ->orderBy('students.full_name')
                ->select('sts_placements.*')
                ->get();
        }

        $lecturers = Lecturer::orderBy('name')->get();

        return view('sts-placements.index', compact('term', 'placements', 'lecturers'));
    }

    /**
     * Assign a supervisor (lecturer) to a placement.
     */
    public function assignSupervisor(Request $request, StsPlacement $stsPlacement)
    {
        $request->validate(['lecturer_id' => 'required|exists:lecturers,id']);

        $stsPlacement->update([
            'lecturer_id' => $request->lecturer_id,
            'supervisor_assigned_at' => now(),
        ]);

        return back()->with('success', 'Supervisor assigned.');
    }
}
