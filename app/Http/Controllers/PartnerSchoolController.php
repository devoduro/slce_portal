<?php

namespace App\Http\Controllers;

use App\Exports\PartnerSchoolTemplateExport;
use App\Imports\PartnerSchoolImport;
use App\Models\PartnerSchool;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use Illuminate\Http\Request;
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
            'name', 'location', 'category',
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
            'name', 'location', 'category',
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
     * Shared validation rules for store/update.
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'category' => 'required|in:early_grade,upper_primary,jhs',
            'capacity_level_100' => 'required|integer|min:0',
            'capacity_level_200' => 'required|integer|min:0',
            'capacity_level_300' => 'required|integer|min:0',
            'capacity_level_400' => 'required|integer|min:0',
        ];
    }
}
