<?php

namespace App\Http\Controllers;

use App\Exports\FeeStructureTemplateExport;
use App\Imports\FeeStructureImport;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class FeeStructureController extends Controller
{
    /**
     * Display a listing of the resource, grouped by academic year.
     */
    public function index()
    {
        $feeStructures = FeeStructure::with(['academicYear', 'programme'])
            ->get()
            ->groupBy(function ($structure) {
                return $structure->academicYear->name ?? 'Unknown';
            })
            ->sortByDesc(function ($group, $yearName) {
                return (int) explode('/', $yearName)[0];
            });

        return view('fees.structures.index', compact('feeStructures'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $academicYears = AcademicYear::chronological()->get();
        $programmes = Programme::orderBy('name')->get();

        return view('fees.structures.create', compact('academicYears', 'programmes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'nullable|integer|min:100|max:800',
            'category' => 'required|in:' . implode(',', array_keys(FeeCategory::options())),
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fee-structures.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();
        if (($data['level'] ?? '') === '') {
            $data['level'] = null;
        }

        if ($this->duplicateExists($data['academic_year_id'], $data['programme_id'], $data['level'], $data['category'])) {
            return redirect()->route('fee-structures.create')
                ->withErrors(['level' => 'A fee structure already exists for this academic year, programme, level and category.'])
                ->withInput();
        }

        FeeStructure::create($data);

        return redirect()->route('fee-structures.index')
            ->with('success', 'Fee structure created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FeeStructure $feeStructure)
    {
        $academicYears = AcademicYear::chronological()->get();
        $programmes = Programme::orderBy('name')->get();

        return view('fees.structures.edit', compact('feeStructure', 'academicYears', 'programmes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeeStructure $feeStructure)
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'nullable|integer|min:100|max:800',
            'category' => 'required|in:' . implode(',', array_keys(FeeCategory::options())),
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fee-structures.edit', $feeStructure)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();
        if (($data['level'] ?? '') === '') {
            $data['level'] = null;
        }

        if ($this->duplicateExists($data['academic_year_id'], $data['programme_id'], $data['level'], $data['category'], $feeStructure->id)) {
            return redirect()->route('fee-structures.edit', $feeStructure)
                ->withErrors(['level' => 'A fee structure already exists for this academic year, programme, level and category.'])
                ->withInput();
        }

        $feeStructure->update($data);

        return redirect()->route('fee-structures.index')
            ->with('success', 'Fee structure updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->delete();

        return redirect()->route('fee-structures.index')
            ->with('success', 'Fee structure deleted successfully.');
    }

    /**
     * Show the bulk-upload form for programme-wide fee structures.
     */
    public function uploadForm()
    {
        return view('fees.structures.upload');
    }

    /**
     * Handle the bulk upload of programme-wide fee structures.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fee-structures.upload')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new FeeStructureImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} record(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('fee-structures.index')->with('warning', $message);
            }

            return redirect()->route('fee-structures.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('fee-structures.upload')
                ->with('error', 'Error importing fee structures: ' . $e->getMessage());
        }
    }

    /**
     * Download the fee structure upload template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new FeeStructureTemplateExport, 'fee_structures_template.xlsx');
    }

    /**
     * Determine whether a fee structure already exists for the given combination.
     */
    protected function duplicateExists(int $academicYearId, int $programmeId, ?int $level, string $category, ?int $excludeId = null): bool
    {
        $query = FeeStructure::where('academic_year_id', $academicYearId)
            ->where('programme_id', $programmeId)
            ->where('category', $category)
            ->where(function ($q) use ($level) {
                $level === null ? $q->whereNull('level') : $q->where('level', $level);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
