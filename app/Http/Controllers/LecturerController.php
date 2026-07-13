<?php

namespace App\Http\Controllers;

use App\Exports\LecturerExport;
use App\Exports\LecturerTemplateExport;
use App\Imports\LecturerImport;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class LecturerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $lecturers = $this->buildLecturerQuery($request)
            ->orderBy('name')->paginate(20)->withQueryString();

        $this->attachWorkload($lecturers);

        $departments = Department::orderBy('name')->get();
        $courses = Course::orderBy('code')->get();

        return view('lecturers.index', compact('lecturers', 'departments', 'courses'));
    }

    /**
     * Export the current filtered lecturer list to Excel.
     */
    public function exportExcel(Request $request)
    {
        $lecturers = $this->buildLecturerQuery($request)->orderBy('name')->get();
        $this->attachWorkload($lecturers);

        return Excel::download(new LecturerExport($lecturers), 'lecturers.xlsx');
    }

    /**
     * Export the current filtered lecturer list to PDF.
     */
    public function exportPdf(Request $request)
    {
        $lecturers = $this->buildLecturerQuery($request)->orderBy('name')->get();
        $this->attachWorkload($lecturers);

        $pdf = PDF::loadView('lecturers.export-pdf', compact('lecturers'))->setPaper('a4', 'landscape');

        return $pdf->download('lecturers.pdf');
    }

    /**
     * Build the filtered lecturer query shared by the on-screen list and both exports.
     */
    protected function buildLecturerQuery(Request $request)
    {
        $query = Lecturer::withCount('courses')->with('department');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('course_id')) {
            $query->whereHas('courses', fn ($q) => $q->where('courses.id', $request->course_id));
        }

        return $query;
    }

    /**
     * Attach each lecturer's current-semester timetable workload (classes taught and total
     * credit hours) via a single grouped query, rather than querying per lecturer.
     */
    protected function attachWorkload($lecturers): void
    {
        $lecturerIds = collect($lecturers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $lecturers->items() : $lecturers)
            ->pluck('id');

        if ($lecturerIds->isEmpty()) {
            return;
        }

        $currentSemester = Semester::where('is_current', true)->first();

        $workloads = TimetableEntry::join('courses', 'courses.id', '=', 'timetable_entries.course_id')
            ->whereIn('timetable_entries.lecturer_id', $lecturerIds)
            ->when($currentSemester, fn ($q) => $q->where('timetable_entries.semester_id', $currentSemester->id))
            ->selectRaw('timetable_entries.lecturer_id, COUNT(*) as classes, SUM(courses.credit_hours) as workload')
            ->groupBy('timetable_entries.lecturer_id')
            ->get()
            ->keyBy('lecturer_id');

        foreach ($lecturers as $lecturer) {
            $lecturer->workload_classes = (int) ($workloads[$lecturer->id]->classes ?? 0);
            $lecturer->workload_credit = (float) ($workloads[$lecturer->id]->workload ?? 0);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('lecturers.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('lecturers.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->only(['name', 'email', 'phone', 'staff_id', 'department_id']);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('lecturer-photos', 'public');
        }

        Lecturer::create($data);

        return redirect()->route('lecturers.index')
            ->with('success', 'Lecturer added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lecturer $lecturer)
    {
        $lecturer->load(['department', 'courses.semester', 'timetableEntries.classGroup', 'timetableEntries.venue', 'timetableEntries.semester', 'user']);

        $workloadBySemester = TimetableController::workloadBySemesterForLecturer($lecturer->id);

        return view('lecturers.show', compact('lecturer', 'workloadBySemester'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lecturer $lecturer)
    {
        $departments = Department::orderBy('name')->get();

        return view('lecturers.edit', compact('lecturer', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lecturer $lecturer)
    {
        $validator = Validator::make($request->all(), $this->rules($lecturer->id));

        if ($validator->fails()) {
            return redirect()->route('lecturers.edit', $lecturer)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->only(['name', 'email', 'phone', 'staff_id', 'department_id']);

        if ($request->hasFile('profile_photo')) {
            if ($lecturer->profile_photo) {
                Storage::disk('public')->delete($lecturer->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('lecturer-photos', 'public');
        }

        $lecturer->update($data);

        return redirect()->route('lecturers.index')
            ->with('success', 'Lecturer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lecturer $lecturer)
    {
        if ($lecturer->courses()->count() > 0) {
            return redirect()->route('lecturers.index')
                ->with('error', 'Cannot delete a lecturer who is assigned to one or more courses.');
        }

        if ($lecturer->timetableEntries()->count() > 0) {
            return redirect()->route('lecturers.index')
                ->with('error', 'Cannot delete a lecturer who is assigned to one or more timetable entries.');
        }

        $lecturer->delete();

        return redirect()->route('lecturers.index')
            ->with('success', 'Lecturer deleted successfully.');
    }

    /**
     * Create a portal login for the lecturer.
     */
    public function createUserAccount(Lecturer $lecturer)
    {
        if ($lecturer->user) {
            return redirect()->route('lecturers.show', $lecturer)
                ->with('warning', 'This lecturer already has a user account.');
        }

        if (!$lecturer->email) {
            return redirect()->route('lecturers.show', $lecturer)
                ->with('error', 'This lecturer has no email address on file. Add one before creating a login.');
        }

        $temporaryPassword = Str::password(12);

        try {
            User::create([
                'name' => $lecturer->name,
                'email' => $lecturer->email,
                'password' => Hash::make($temporaryPassword),
                'role' => 'admin',
                'lecturer_id' => $lecturer->id,
                'first_login' => true,
            ])->assignRole('Lecturer');

            return redirect()->route('lecturers.show', $lecturer)
                ->with('success', "User account created successfully. Temporary password: {$temporaryPassword} (share this with the lecturer securely — it will not be shown again).");
        } catch (\Exception $e) {
            return redirect()->route('lecturers.show', $lecturer)
                ->with('error', 'Failed to create user account. Error: ' . $e->getMessage());
        }
    }

    /**
     * Show the bulk lecturer import form.
     */
    public function importForm()
    {
        return view('lecturers.import');
    }

    /**
     * Handle the bulk import of lecturers from Excel/CSV.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->route('lecturers.import.form')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $import = new LecturerImport();
            Excel::import($import, $request->file('excel_file'));

            $stats = $import->getStats();
            $message = "Processed {$stats['processed']} record(s), skipped {$stats['skipped']}.";

            if (!empty($stats['errors'])) {
                $message .= ' Issues: ' . implode(' | ', array_slice($stats['errors'], 0, 5));
                if (count($stats['errors']) > 5) {
                    $message .= ' (+' . (count($stats['errors']) - 5) . ' more)';
                }

                return redirect()->route('lecturers.index')->with('warning', $message);
            }

            return redirect()->route('lecturers.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('lecturers.import.form')
                ->with('error', 'Error importing lecturers: ' . $e->getMessage());
        }
    }

    /**
     * Download the lecturer import template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new LecturerTemplateExport, 'lecturers_template.xlsx');
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('lecturers', 'email')->ignore($ignoreId)],
            'phone' => 'nullable|string|max:20',
            'staff_id' => ['nullable', 'string', 'max:50', Rule::unique('lecturers', 'staff_id')->ignore($ignoreId)],
            'department_id' => 'nullable|exists:departments,id',
            'profile_photo' => 'nullable|image|max:2048',
        ];
    }
}
