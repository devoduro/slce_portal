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

        $perPageInput = $request->input('per_page', 100);
        if ($perPageInput === 'all') {
            $perPage = max((clone $query)->count(), 1);
        } else {
            $perPage = (int) $perPageInput;
            if (!in_array($perPage, [100, 200, 300], true)) {
                $perPage = 100;
            }
        }

        $schools = $query->orderBy('name')->paginate($perPage)->withQueryString();

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

        $data = $request->only([
            'name', 'location', 'category', 'type',
            'capacity_level_100', 'capacity_level_200', 'capacity_level_300', 'capacity_level_400', 'total_capacity',
        ]);
        $data['total_capacity'] = $data['total_capacity'] ?: $this->defaultTotalCapacity($data);

        PartnerSchool::create($data);

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

        $data = $request->only([
            'name', 'location', 'category', 'type',
            'capacity_level_100', 'capacity_level_200', 'capacity_level_300', 'capacity_level_400', 'total_capacity',
        ]);
        $data['total_capacity'] = $data['total_capacity'] ?: $this->defaultTotalCapacity($data);

        $partnerSchool->update($data);

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
     * Delete multiple partner schools at once, skipping any that already have placements
     * (same guard as the single destroy() above) rather than failing the whole batch.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'school_ids' => 'required|array',
            'school_ids.*' => 'exists:partner_schools,id',
        ]);

        $schools = PartnerSchool::whereIn('id', $request->school_ids)->withCount('placements')->get();

        $deletable = $schools->where('placements_count', 0);
        $blocked = $schools->where('placements_count', '>', 0);

        PartnerSchool::whereIn('id', $deletable->pluck('id'))->delete();

        $message = 'Deleted ' . $deletable->count() . ' school(s).';

        if ($blocked->isNotEmpty()) {
            $message .= ' Skipped ' . $blocked->count() . ' with existing placements: ' . $blocked->pluck('name')->implode(', ') . '.';

            return redirect()->route('partner-schools.index')->with('warning', $message);
        }

        return redirect()->route('partner-schools.index')->with('success', $message);
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

            // How many students are eligible for this type THIS TERM (i.e. their CURRENT level
            // against the term's own semester/cutoff) vs how many have actually picked a school
            // yet - distinct from the per-school rosters below, which only show the latter.
            // Recomputed live rather than trusting each placement's stored `type` column, since
            // that value is only set once (StsTermController::activate()'s firstOrCreate) and
            // goes stale if the student's level changes afterward, or if a later semester's
            // rules (e.g. Level 400 only continues Internship in its first semester) changed
            // after this term's placements were originally seeded.
            if ($term) {
                $termSemesterNumber = $term->semester->semester_number;

                $eligibleCount = 0;
                $selectedCount = 0;

                StsPlacement::where('sts_term_id', $term->id)->with('student')->chunk(500, function ($placements) use ($term, $termSemesterNumber, $request, &$eligibleCount, &$selectedCount) {
                    foreach ($placements as $placement) {
                        $level = $placement->student->level ?? $placement->level;
                        $liveType = StsPlacement::determineType((int) $level, $termSemesterNumber, $term->internship_level_cutoff, $term->internship_semester_cutoff);

                        if ($liveType !== $request->type) {
                            continue;
                        }

                        $eligibleCount++;

                        if ($placement->partner_school_id) {
                            $selectedCount++;
                        }
                    }
                });

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

        $totalStudents = $placementsBySchool->sum(fn ($roster) => $roster->count());

        $settings = DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('sts.letters.school-roster', compact('schools', 'placementsBySchool', 'term', 'settings', 'reportTitle', 'eligibilitySummary', 'totalStudents'));
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'category' => 'required|in:early_grade,upper_primary,jhs_le,jhs_he',
            'type' => 'required|in:sts,internship',
            'capacity_level_100' => 'required|integer|min:0',
            'capacity_level_200' => 'required|integer|min:0',
            'capacity_level_300' => 'required|integer|min:0',
            'capacity_level_400' => 'required|integer|min:0',
            'total_capacity' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Informational overall capacity when left blank on the form/upload - the sum of the
     * per-level quotas, which are what actually gate selection in availableQuota().
     */
    protected function defaultTotalCapacity(array $data): int
    {
        return (int) ($data['capacity_level_100'] ?? 0)
            + (int) ($data['capacity_level_200'] ?? 0)
            + (int) ($data['capacity_level_300'] ?? 0)
            + (int) ($data['capacity_level_400'] ?? 0);
    }
}
