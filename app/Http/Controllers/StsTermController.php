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

        StsTerm::create($request->only(['semester_id', 'name', 'proposed_start_date', 'proposed_end_date', 'internship_level_cutoff', 'internship_semester_cutoff']));

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

        $stsTerm->update($request->only(['semester_id', 'name', 'proposed_start_date', 'proposed_end_date', 'internship_level_cutoff', 'internship_semester_cutoff']));

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
            // Plain query-builder updates, not $stsTerm->update() - if $stsTerm was already the
            // current term when fetched (e.g. re-activating to pick up a data fix), Eloquent's
            // dirty-checking would see is_current going true -> true as "no change" and silently
            // skip the actual UPDATE, leaving the row flipped to false by the line above.
            StsTerm::query()->update(['is_current' => false]);
            StsTerm::whereKey($stsTerm->id)->update(['is_current' => true]);
            $stsTerm->refresh();

            $stsCourse = Course::where('code', 'STS')->where('is_sts_course', true)->first();
            $internshipCourse = Course::where('code', 'INTERNSHIP')->where('is_sts_course', true)->first();
            $academicYearId = $stsTerm->semester->academic_year_id;
            $termSemesterNumber = $stsTerm->semester->semester_number;

            Student::where('status', 'active')
                ->whereNotNull('level')
                ->whereHas('programme', fn ($q) => $q->whereNotNull('sts_category'))
                ->chunkById(200, function ($students) use ($stsTerm, $stsCourse, $internshipCourse, $academicYearId, $termSemesterNumber) {
                    foreach ($students as $student) {
                        $type = StsPlacement::determineType(
                            (int) $student->level,
                            $termSemesterNumber,
                            $stsTerm->internship_level_cutoff,
                            $stsTerm->internship_semester_cutoff
                        );

                        // null = beyond the placement system entirely (e.g. Level 400 Second
                        // Semester onward, once their one continuing internship semester is over).
                        if ($type === null) {
                            continue;
                        }

                        $attributes = ['level' => $student->level, 'type' => $type];

                        // Level above the cutoff (e.g. 400) continuing an internship: carry over
                        // the school/supervisor(s) from their Level-300 internship placement
                        // rather than making them (or an admin) select again - it's the same
                        // placement, just being scored for one more semester.
                        if ($type === StsPlacement::TYPE_INTERNSHIP && (int) $student->level > $stsTerm->internship_level_cutoff) {
                            $attributes += $this->carryOverInternshipPlacement($student, $stsTerm);
                        }

                        StsPlacement::firstOrCreate(
                            ['student_id' => $student->id, 'sts_term_id' => $stsTerm->id],
                            $attributes
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
     * Find the student's most recent Level-cutoff (e.g. 300) internship placement with a school
     * already assigned, and return its school/supervisor(s) to seed the continuation placement
     * with - so the Level 400 First Semester "same data" scoring term starts pre-filled instead
     * of requiring a fresh selection.
     */
    protected function carryOverInternshipPlacement(Student $student, StsTerm $stsTerm): array
    {
        $previous = StsPlacement::where('student_id', $student->id)
            ->where('level', $stsTerm->internship_level_cutoff)
            ->where('type', StsPlacement::TYPE_INTERNSHIP)
            ->whereNotNull('partner_school_id')
            ->orderByDesc('id')
            ->first();

        if (!$previous) {
            return [];
        }

        return [
            'partner_school_id' => $previous->partner_school_id,
            'lecturer_id' => $previous->lecturer_id,
            'second_lecturer_id' => $previous->second_lecturer_id,
            'supervisor_assigned_at' => $previous->supervisor_assigned_at,
        ];
    }

    /**
     * Deactivate this term without activating a replacement - existing placements and
     * scores are untouched, but students immediately lose access to /student/sts (every
     * eligibility/placement lookup there is scoped to the current term) until a term is
     * activated again. Uses a query-builder update, not $stsTerm->update(), for the same
     * dirty-checking reason documented in activate() above.
     */
    public function deactivate(StsTerm $stsTerm)
    {
        if (!$stsTerm->is_current) {
            return redirect()->route('sts-terms.index')
                ->with('error', "\"{$stsTerm->name}\" is not currently active.");
        }

        StsTerm::whereKey($stsTerm->id)->update(['is_current' => false]);

        return redirect()->route('sts-terms.index')
            ->with('success', "\"{$stsTerm->name}\" deactivated. Students no longer have access to it.");
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
            'internship_semester_cutoff' => 'required|integer|in:1,2',
        ];
    }
}
