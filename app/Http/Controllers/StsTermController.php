<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StsPlacement;
use App\Models\StsTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StsTermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $terms = StsTerm::with('semester.academicYear')->orderBy('proposed_start_date', 'desc')->paginate(20);

        return view('sts-terms.index', compact('terms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $semesters = Semester::with('academicYear')->orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('sts-terms.create', compact('semesters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('sts-terms.create')
                ->withErrors($validator)
                ->withInput();
        }

        StsTerm::create($request->only(['semester_id', 'name', 'proposed_start_date', 'proposed_end_date', 'internship_level_cutoff']));

        return redirect()->route('sts-terms.index')
            ->with('success', 'STS term created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StsTerm $stsTerm)
    {
        $semesters = Semester::with('academicYear')->orderBy('academic_year_id', 'desc')->orderBy('semester_number')->get();

        return view('sts-terms.edit', compact('stsTerm', 'semesters'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StsTerm $stsTerm)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('sts-terms.edit', $stsTerm)
                ->withErrors($validator)
                ->withInput();
        }

        $stsTerm->update($request->only(['semester_id', 'name', 'proposed_start_date', 'proposed_end_date', 'internship_level_cutoff']));

        return redirect()->route('sts-terms.index')
            ->with('success', 'STS term updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StsTerm $stsTerm)
    {
        if ($stsTerm->placements()->count() > 0) {
            return redirect()->route('sts-terms.index')
                ->with('error', 'Cannot delete an STS term with existing placements.');
        }

        $stsTerm->delete();

        return redirect()->route('sts-terms.index')
            ->with('success', 'STS term deleted successfully.');
    }

    /**
     * Activate this term as the current one and seed placements (+ matching course
     * registrations) for every eligible active student whose programme has an
     * STS category assigned.
     */
    public function activate(StsTerm $stsTerm)
    {
        DB::transaction(function () use ($stsTerm) {
            StsTerm::query()->update(['is_current' => false]);
            $stsTerm->update(['is_current' => true]);

            $stsCourse = Course::where('code', 'STS')->where('is_sts_course', true)->first();
            $internshipCourse = Course::where('code', 'INTERNSHIP')->where('is_sts_course', true)->first();
            $academicYearId = $stsTerm->semester->academic_year_id;

            Student::where('status', 'active')
                ->whereNotNull('level')
                ->whereHas('programme', fn ($q) => $q->whereNotNull('sts_category'))
                ->chunkById(200, function ($students) use ($stsTerm, $stsCourse, $internshipCourse, $academicYearId) {
                    foreach ($students as $student) {
                        $type = StsPlacement::determineType((int) $student->level, $stsTerm->internship_level_cutoff);

                        StsPlacement::firstOrCreate(
                            ['student_id' => $student->id, 'sts_term_id' => $stsTerm->id],
                            ['level' => $student->level, 'type' => $type]
                        );

                        $course = $type === StsPlacement::TYPE_INTERNSHIP ? $internshipCourse : $stsCourse;

                        if ($course) {
                            Registration::firstOrCreate([
                                'student_id' => $student->id,
                                'course_id' => $course->id,
                                'semester_id' => $stsTerm->semester_id,
                                'academic_year_id' => $academicYearId,
                            ], ['status' => 'registered']);
                        }
                    }
                });
        });

        return redirect()->route('sts-terms.index')
            ->with('success', "\"{$stsTerm->name}\" activated and placements seeded for eligible students.");
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function rules(): array
    {
        return [
            'semester_id' => 'required|exists:semesters,id',
            'name' => 'required|string|max:255',
            'proposed_start_date' => 'required|date',
            'proposed_end_date' => 'required|date|after:proposed_start_date',
            'internship_level_cutoff' => 'required|integer|min:100|max:800',
        ];
    }
}
