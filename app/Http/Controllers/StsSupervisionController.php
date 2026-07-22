<?php

namespace App\Http\Controllers;

use App\Models\ContinuousAssessment;
use App\Models\StsPlacement;
use App\Models\StsScoreSetting;
use App\Services\StsAttendanceScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StsSupervisionController extends Controller
{
    /**
     * List the authenticated lecturer's assigned STS/Internship students for the current term,
     * with search/filter/sort - all applied in PHP over the lecturer's own (typically small)
     * placement set rather than a paginated query, since this is scoped to one lecturer's
     * assigned students, not the whole student body.
     */
    public function index(Request $request)
    {
        $lecturer = $this->authLecturerOrAbort();

        $placements = StsPlacement::with(['student.programme', 'partnerSchool', 'stsTerm'])
            ->where(fn ($q) => $q->where('lecturer_id', $lecturer->id)->orWhere('second_lecturer_id', $lecturer->id))
            ->whereHas('stsTerm', fn ($q) => $q->where('is_current', true))
            ->get();

        // Dropdown options derived from this lecturer's own assigned students only, so the
        // filters never offer a programme/level/type that would return zero results.
        $programmes = $placements->pluck('student.programme')->filter()->unique('id')->sortBy('name')->values();
        $levels = $placements->pluck('level')->unique()->sort()->values();

        // Whether the lecturer has any students at all this term, independent of the filters
        // below - the "Print Supervisor Letter" button covers all of them, not just the
        // currently filtered view, so it shouldn't disappear just because a filter matched none.
        $hasAnyPlacements = $placements->isNotEmpty();

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $placements = $placements->filter(fn ($p) => str_contains(strtolower($p->student->full_name ?? ''), $search)
                || str_contains(strtolower($p->student->index_number ?? ''), $search));
        }

        if ($request->filled('type')) {
            $placements = $placements->where('type', $request->type);
        }

        if ($request->filled('programme_id')) {
            $placements = $placements->filter(fn ($p) => (int) ($p->student->programme_id ?? 0) === (int) $request->programme_id);
        }

        if ($request->filled('level')) {
            $placements = $placements->where('level', (int) $request->level);
        }

        $sort = $request->input('sort', 'name');
        $placements = match ($sort) {
            'level' => $placements->sortBy('level'),
            'type' => $placements->sortBy('type'),
            'school' => $placements->sortBy(fn ($p) => $p->partnerSchool->name ?? ''),
            default => $placements->sortBy(fn ($p) => $p->student->full_name ?? ''),
        };

        $placements = $placements->values();

        return view('sts-supervision.index', compact('lecturer', 'placements', 'programmes', 'levels', 'sort', 'hasAnyPlacements'));
    }

    /**
     * Show the score entry form for one assigned student.
     */
    public function scoreForm(StsPlacement $stsPlacement)
    {
        $lecturer = $this->authLecturerOrAbort();
        abort_unless(in_array($lecturer->id, [$stsPlacement->lecturer_id, $stsPlacement->second_lecturer_id], true), 403);

        $stsPlacement->load(['student.programme', 'partnerSchool', 'stsTerm.semester']);

        $setting = StsScoreSetting::where('level', $stsPlacement->level)->first();
        $term = $stsPlacement->stsTerm;

        $ca = ContinuousAssessment::where('student_id', $stsPlacement->student_id)
            ->where('course_id', $stsPlacement->course()->id)
            ->where('semester_id', $term->semester_id)
            ->where('academic_year_id', $term->semester->academic_year_id)
            ->first();

        $attendanceScore = StsAttendanceScoreCalculator::score($stsPlacement);

        return view('sts-supervision.score', compact('stsPlacement', 'setting', 'ca', 'attendanceScore'));
    }

    /**
     * Save supervisor-entered scores into the shared Continuous Assessment table.
     */
    public function scoreStore(Request $request, StsPlacement $stsPlacement)
    {
        $lecturer = $this->authLecturerOrAbort();
        abort_unless(in_array($lecturer->id, [$stsPlacement->lecturer_id, $stsPlacement->second_lecturer_id], true), 403);

        $setting = StsScoreSetting::where('level', $stsPlacement->level)->first();

        $data = [];
        $componentMax = [
            'project' => $setting?->project_max,
            'assignment' => $setting?->assignment_max,
            'mid_semester' => $setting?->mid_semester_max,
        ];

        foreach (['project' => 'project_score', 'assignment' => 'assignment_score', 'mid_semester' => 'mid_semester_score'] as $key => $column) {
            if (!$request->filled("scores.{$key}")) {
                continue;
            }

            $value = (float) $request->input("scores.{$key}");
            $max = $componentMax[$key];

            if ($value < 0 || ($max !== null && $value > (float) $max)) {
                return back()->withErrors(["scores.{$key}" => 'Must be between 0 and ' . ($max ?? 'N/A') . '.']);
            }

            $data[$column] = $value;
        }

        $term = $stsPlacement->stsTerm;

        ContinuousAssessment::updateOrCreate([
            'student_id' => $stsPlacement->student_id,
            'course_id' => $stsPlacement->course()->id,
            'semester_id' => $term->semester_id,
            'academic_year_id' => $term->semester->academic_year_id,
        ], $data);

        return redirect()->route('sts-supervision.index')
            ->with('success', 'Scores saved.');
    }

    /**
     * Print the supervisor's letter listing all currently assigned students.
     */
    public function printLetter()
    {
        $lecturer = $this->authLecturerOrAbort();

        $placements = StsPlacement::with(['student.programme', 'partnerSchool'])
            ->where(fn ($q) => $q->where('lecturer_id', $lecturer->id)->orWhere('second_lecturer_id', $lecturer->id))
            ->whereHas('stsTerm', fn ($q) => $q->where('is_current', true))
            ->get();

        $term = $placements->first()?->stsTerm;

        $settings = DB::table('settings')->where('category', 'institution')->pluck('value', 'key')->toArray();

        return view('sts.letters.supervisor', compact('lecturer', 'term', 'placements', 'settings'));
    }

    /**
     * Resolve the lecturer profile linked to the authenticated user, or deny access.
     */
    protected function authLecturerOrAbort()
    {
        $lecturer = Auth::user()->lecturer;

        abort_unless($lecturer, 403, 'This area is only available to lecturer accounts.');

        return $lecturer;
    }
}
