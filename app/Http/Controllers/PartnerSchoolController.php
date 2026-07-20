<?php

namespace App\Http\Controllers;

use App\Exports\PartnerSchoolTemplateExport;
use App\Imports\PartnerSchoolImport;
use App\Models\PartnerSchool;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PartnerSchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PartnerSchool::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $schools = $query->orderBy('name')->paginate(20)->withQueryString();

        $currentTerm = StsTerm::where('is_current', true)->first();

        // Grouped by school + level in one query rather than per-row lookups, so the
        // "Placed / Open" column below doesn't turn this listing into an N+1.
        $placedCounts = collect();

        if ($currentTerm) {
            $placedCounts = StsPlacement::where('sts_term_id', $currentTerm->id)
                ->whereIn('partner_school_id', $schools->pluck('id'))
                ->selectRaw('partner_school_id, level, COUNT(*) as total')
                ->groupBy('partner_school_id', 'level')
                ->get()
                ->groupBy('partner_school_id');
        }

        return view('partner-schools.index', compact('schools', 'currentTerm', 'placedCounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('partner-schools.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('partner-schools.create')
                ->withErrors($validator)
                ->withInput();
        }

        PartnerSchool::create($request->only([
            'name', 'location', 'category', 'type',
            'capacity_level_100', 'capacity_level_200', 'capacity_level_300', 'capacity_level_400',
        ]));

        return redirect()->route('partner-schools.index')
            ->with('success', 'Partner school added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PartnerSchool $partnerSchool)
    {
        return view('partner-schools.edit', compact('partnerSchool'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PartnerSchool $partnerSchool)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('partner-schools.edit', $partnerSchool)
                ->withErrors($validator)
                ->withInput();
        }

        $partnerSchool->update($request->only([
            'name', 'location', 'category', 'type',
            'capacity_level_100', 'capacity_level_200', 'capacity_level_300', 'capacity_level_400',
        ]));

        return redirect()->route('partner-schools.index')
            ->with('success', 'Partner school updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PartnerSchool $partnerSchool)
    {
        if ($partnerSchool->placements()->count() > 0) {
            return redirect()->route('partner-schools.index')
                ->with('error', 'Cannot delete a partner school with existing placements.');
        }

        $partnerSchool->delete();

        return redirect()->route('partner-schools.index')
            ->with('success', 'Partner school deleted successfully.');
    }

    /**
     * Show the bulk partner school import form.
     */
    public function importForm()
    {
        return view('partner-schools.import');
    }

    /**
     * Handle the bulk import of partner schools from Excel/CSV.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('partner-schools.import.form')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new PartnerSchoolImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} record(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('partner-schools.index')->with('warning', $message);
            }

            return redirect()->route('partner-schools.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('partner-schools.import.form')
                ->with('error', 'Error importing partner schools: ' . $e->getMessage());
        }
    }

    /**
     * Download the partner school import template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new PartnerSchoolTemplateExport, 'partner_schools_template.xlsx');
    }

    /**
     * Printable roster of students placed at partner school(s) for the current STS term -
     * a single school (?partner_school_id=), all schools of one type (?type=sts|internship),
     * or every school (no filter), each with the students who selected it.
     */
    public function printRoster(Request $request)
    {
        $term = StsTerm::where('is_current', true)->first();

        $query = PartnerSchool::query();
        $reportTitle = 'Partner School Rosters';
        $eligibilitySummary = null;

        if ($request->filled('partner_school_id')) {
            $school = PartnerSchool::findOrFail($request->partner_school_id);
            $query->where('id', $school->id);
            $reportTitle = $school->name . ' - Student Roster';
        } elseif ($request->filled('type')) {
            $query->where('type', $request->type);
            $reportTitle = ($request->type === 'internship' ? 'Internship' : 'STS') . ' Schools - Student Rosters';

            // How many students are eligible for this type this term (i.e. their level/term
            // cutoff puts them in this type) vs how many have actually picked a school yet -
            // distinct from the per-school rosters below, which only show the latter.
            if ($term) {
                $eligibleCount = StsPlacement::where('sts_term_id', $term->id)->where('type', $request->type)->count();
                $selectedCount = StsPlacement::where('sts_term_id', $term->id)->where('type', $request->type)->whereNotNull('partner_school_id')->count();
                $eligibilitySummary = [
                    'eligible' => $eligibleCount,
                    'selected' => $selectedCount,
                    'unplaced' => $eligibleCount - $selectedCount,
                ];
            }
        }

        $schools = $query->orderBy('type')->orderBy('name')->get();

        $placementsBySchool = collect();
        if ($term) {
            $placementsBySchool = StsPlacement::with(['student.programme', 'lecturer', 'secondLecturer'])
                ->where('sts_term_id', $term->id)
                ->whereIn('partner_school_id', $schools->pluck('id'))
                ->get()
                ->groupBy('partner_school_id');
        }

        $settings = DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('sts.letters.school-roster', compact('schools', 'placementsBySchool', 'term', 'settings', 'reportTitle', 'eligibilitySummary'));
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'category' => 'required|in:early_grade,upper_primary,jhs',
            'type' => 'required|in:sts,internship',
            'capacity_level_100' => 'required|integer|min:0',
            'capacity_level_200' => 'required|integer|min:0',
            'capacity_level_300' => 'required|integer|min:0',
            'capacity_level_400' => 'required|integer|min:0',
        ];
    }
}
