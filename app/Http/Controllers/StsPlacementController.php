<?php

namespace App\Http\Controllers;

use App\Exports\StsPlacementsExport;
use App\Exports\StsSchoolTemplateExport;
use App\Exports\StsSupervisorTemplateExport;
use App\Imports\StsSchoolImport;
use App\Imports\StsSupervisorImport;
use App\Models\Lecturer;
use App\Models\PartnerSchool;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use App\Services\StsPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

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
            $placements = $this->filteredPlacements($request, $term)
                ->with(['student.programme', 'partnerSchool', 'lecturer', 'secondLecturer'])
                ->paginate($perPage)
                ->withQueryString();
        }

        $lecturers = Lecturer::orderBy('name')->get();
        $partnerSchools = PartnerSchool::orderBy('name')->get();

        return view('sts-placements.index', compact('term', 'placements', 'lecturers', 'partnerSchools'));
    }

    /**
     * Export the current (filtered) placement list to Excel. Shares filteredPlacements() with
     * index() so the spreadsheet always matches what the page is showing.
     */
    public function exportExcel(Request $request)
    {
        $term = StsTerm::where('is_current', true)->first();

        if (!$term) {
            return redirect()->route('sts-placements.index')
                ->with('error', 'No STS term is currently active, so there are no placements to export.');
        }

        $filename = 'sts-placements-' . str_replace(['/', ' '], ['-', '-'], $term->name) . '.xlsx';

        return Excel::download(new StsPlacementsExport($this->filteredPlacements($request, $term)), $filename);
    }

    /**
     * The term's placements with the page's category/type/search filters applied. Ordered by
     * student name, with the primary key as a tiebreaker so chunked exports can't skip or
     * repeat rows where two students share a name.
     */
    protected function filteredPlacements(Request $request, StsTerm $term)
    {
        return StsPlacement::query()
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
            ->orderBy('sts_placements.id')
            ->select('sts_placements.*');
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

    /**
     * Show the bulk supervisor-upload form, scoped to STS or Internship via ?type=.
     */
    public function supervisorsUploadForm(Request $request)
    {
        $type = $request->input('type') === 'internship' ? 'internship' : 'sts';
        $term = StsTerm::where('is_current', true)->first();

        return view('sts-placements.supervisors-upload', compact('type', 'term'));
    }

    /**
     * Handle the bulk upload of STS/Internship supervisors, matched by student index number
     * and lecturer name. Only touches placements of the given type in the current term.
     */
    public function supervisorsImport(Request $request)
    {
        $type = $request->input('type') === 'internship' ? 'internship' : 'sts';

        $term = StsTerm::where('is_current', true)->first();

        if (!$term) {
            return redirect()->route('sts-placements.supervisors.upload', ['type' => $type])
                ->with('error', 'No active STS term to import supervisors into.');
        }

        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('sts-placements.supervisors.upload', ['type' => $type])
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new StsSupervisorImport($term->id, $type);
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} " . ucfirst($type) . " supervisor assignment(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('sts-placements.index', ['type' => $type])->with('warning', $message);
            }

            return redirect()->route('sts-placements.index', ['type' => $type])->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('sts-placements.supervisors.upload', ['type' => $type])
                ->with('error', 'Error importing supervisors: ' . $e->getMessage());
        }
    }

    /**
     * Download the supervisor upload template, pre-filled with two real lecturer names so
     * admins can see the exact-match format expected.
     */
    public function supervisorsTemplate(Request $request)
    {
        $type = $request->input('type') === 'internship' ? 'internship' : 'sts';
        $sampleNames = Lecturer::orderBy('name')->limit(2)->pluck('name')->all();

        return Excel::download(new StsSupervisorTemplateExport($sampleNames), "{$type}_supervisors_template.xlsx");
    }

    /**
     * Show the bulk partner-school-upload form, scoped to STS or Internship via ?type=.
     */
    public function schoolsUploadForm(Request $request)
    {
        $type = $request->input('type') === 'internship' ? 'internship' : 'sts';
        $term = StsTerm::where('is_current', true)->first();

        return view('sts-placements.schools-upload', compact('type', 'term'));
    }

    /**
     * Handle the bulk upload of STS/Internship partner schools, matched by student index
     * number and school name. Only touches placements of the given type in the current term.
     */
    public function schoolsImport(Request $request)
    {
        $type = $request->input('type') === 'internship' ? 'internship' : 'sts';

        $term = StsTerm::where('is_current', true)->first();

        if (!$term) {
            return redirect()->route('sts-placements.schools.upload', ['type' => $type])
                ->with('error', 'No active STS term to import schools into.');
        }

        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('sts-placements.schools.upload', ['type' => $type])
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new StsSchoolImport($term->id, $type);
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} " . ucfirst($type) . " school assignment(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('sts-placements.index', ['type' => $type])->with('warning', $message);
            }

            return redirect()->route('sts-placements.index', ['type' => $type])->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('sts-placements.schools.upload', ['type' => $type])
                ->with('error', 'Error importing schools: ' . $e->getMessage());
        }
    }

    /**
     * Download the school upload template, pre-filled with two real partner school names so
     * admins can see the exact-match format expected.
     */
    public function schoolsTemplate(Request $request)
    {
        $type = $request->input('type') === 'internship' ? 'internship' : 'sts';
        $sampleNames = PartnerSchool::orderBy('name')->limit(2)->pluck('name')->all();

        return Excel::download(new StsSchoolTemplateExport($sampleNames), "{$type}_schools_template.xlsx");
    }
}
