<?php

namespace App\Http\Controllers;

use App\Models\StsPlacement;
use App\Models\StsPlacementScore;
use App\Models\StsScoreCriterion;
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

        $stsPlacement->load(['student.programme', 'partnerSchool', 'stsTerm.semester', 'scores']);

        // The criteria, labels and maximum marks the STS Coordinator defined for this level -
        // the score sheet is whatever they set up, not a fixed set of columns.
        $criteria = StsScoreCriterion::forLevel((int) $stsPlacement->level);
        $existing = $stsPlacement->scores->keyBy('sts_score_criterion_id');

        return view('sts-supervision.score', compact('stsPlacement', 'criteria', 'existing'));
    }

    /**
     * Save supervisor-entered scores into the shared Continuous Assessment table.
     */
    public function scoreStore(Request $request, StsPlacement $stsPlacement)
    {
        $lecturer = $this->authLecturerOrAbort();
        abort_unless(in_array($lecturer->id, [$stsPlacement->lecturer_id, $stsPlacement->second_lecturer_id], true), 403);

        $criteria = StsScoreCriterion::forLevel((int) $stsPlacement->level);

        if ($criteria->isEmpty()) {
            return back()->with('error', "No score sheet has been set up for Level {$stsPlacement->level} yet. Ask the STS Coordinator to define its criteria first.");
        }

        $submitted = (array) $request->input('scores', []);
        $errors = [];
        $values = [];

        foreach ($criteria as $criterion) {
            $raw = $submitted[$criterion->id] ?? null;

            // A blank is "not marked yet", which is different from a zero - a supervisor part-way
            // through a sheet should be able to save what they have.
            if ($raw === null || $raw === '') {
                $values[$criterion->id] = null;

                continue;
            }

            if (!is_numeric($raw)) {
                $errors["scores.{$criterion->id}"] = "{$criterion->label} must be a number.";

                continue;
            }

            $value = (float) $raw;
            $max = (float) $criterion->max_mark;

            if ($value < 0 || $value > $max) {
                $errors["scores.{$criterion->id}"] = "{$criterion->label} must be between 0 and " . rtrim(rtrim(number_format($max, 2), '0'), '.') . '.';

                continue;
            }

            $values[$criterion->id] = $value;
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($stsPlacement, $values) {
            foreach ($values as $criterionId => $value) {
                StsPlacementScore::updateOrCreate([
                    'sts_placement_id' => $stsPlacement->id,
                    'sts_score_criterion_id' => $criterionId,
                ], ['score' => $value]);
            }
        });

        $summary = $stsPlacement->fresh('scores')->scoreSummary();

        return redirect()->route('sts-supervision.index')
            ->with('success', sprintf(
                'Scores saved — %s out of %s across %d criteria.',
                number_format($summary['awarded'], 2),
                number_format($summary['total'], 2),
                $summary['criteria']
            ));
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
