<?php

namespace App\Http\Controllers;

use App\Models\StsScoreCriterion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Lets the STS coordinator define the score sheet itself: which criteria a level is marked on,
 * what each one is called, and how many marks it carries.
 *
 * Criteria are managed a whole level at a time rather than one row at a time, because that is
 * how a score sheet is actually decided - "Level 300 is marked out of 100 across these six
 * things" - and it keeps the running total visible while the sheet is being built.
 */
class StsScoreSettingController extends Controller
{
    /**
     * The levels a score sheet can be defined for.
     */
    protected const LEVELS = [100, 200, 300, 400];

    /**
     * List each level's score sheet with its criteria and total.
     */
    public function index()
    {
        $criteriaByLevel = StsScoreCriterion::orderBy('level')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('level');

        $levels = collect(self::LEVELS)->map(fn (int $level) => [
            'level' => $level,
            'criteria' => $criteriaByLevel->get($level, collect()),
            'total' => (float) $criteriaByLevel->get($level, collect())->sum('max_mark'),
        ]);

        return view('sts-score-settings.index', compact('levels'));
    }

    /**
     * Show the builder for a level that has no score sheet yet.
     */
    public function create(Request $request)
    {
        $usedLevels = StsScoreCriterion::distinct()->pluck('level')->map(fn ($l) => (int) $l)->all();
        $availableLevels = array_values(array_diff(self::LEVELS, $usedLevels));

        $level = (int) $request->input('level', $availableLevels[0] ?? 0);

        // Most colleges mark every level on broadly the same things, so offer to start from a
        // level that is already set up rather than retyping the whole sheet.
        $copyFrom = $request->filled('copy_from') ? (int) $request->copy_from : null;
        $criteria = $copyFrom ? StsScoreCriterion::forLevel($copyFrom) : collect();

        return view('sts-score-settings.create', compact('level', 'availableLevels', 'usedLevels', 'criteria', 'copyFrom'));
    }

    /**
     * Save a new level's score sheet.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules() + [
            'level' => 'required|integer|in:' . implode(',', self::LEVELS) . '|unique:sts_score_criteria,level',
        ], $this->messages());

        if ($validator->fails()) {
            return redirect()->route('sts-score-settings.create', ['level' => $request->level])
                ->withErrors($validator)
                ->withInput();
        }

        $this->saveCriteria((int) $request->level, $request->input('criteria', []));

        return redirect()->route('sts-score-settings.index')
            ->with('success', "Level {$request->level} score sheet saved.");
    }

    /**
     * Show the builder for a level that already has a score sheet.
     */
    public function edit(int $level)
    {
        abort_unless(in_array($level, self::LEVELS, true), 404);

        $criteria = StsScoreCriterion::forLevel($level);

        // A criterion that supervisors have already marked against cannot be dropped without
        // throwing those marks away, so the form locks it instead of offering a remove button.
        $markedIds = StsScoreCriterion::where('level', $level)
            ->whereHas('scores', fn ($query) => $query->whereNotNull('score'))
            ->pluck('id')
            ->all();

        return view('sts-score-settings.edit', compact('level', 'criteria', 'markedIds'));
    }

    /**
     * Replace a level's score sheet with the submitted one.
     */
    public function update(Request $request, int $level)
    {
        abort_unless(in_array($level, self::LEVELS, true), 404);

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return redirect()->route('sts-score-settings.edit', $level)
                ->withErrors($validator)
                ->withInput();
        }

        $this->saveCriteria($level, $request->input('criteria', []));

        return redirect()->route('sts-score-settings.index')
            ->with('success', "Level {$level} score sheet updated.");
    }

    /**
     * Remove a level's score sheet entirely.
     */
    public function destroy(int $level)
    {
        abort_unless(in_array($level, self::LEVELS, true), 404);

        $marked = StsScoreCriterion::where('level', $level)
            ->whereHas('scores', fn ($query) => $query->whereNotNull('score'))
            ->exists();

        if ($marked) {
            return redirect()->route('sts-score-settings.index')
                ->with('error', "Level {$level} already has marks recorded against its criteria. Edit the sheet instead of deleting it, so those marks are not lost.");
        }

        StsScoreCriterion::where('level', $level)->delete();

        return redirect()->route('sts-score-settings.index')
            ->with('success', "Level {$level} score sheet removed.");
    }

    /**
     * Write a level's criteria, keeping the ids of rows that already exist.
     *
     * Rows are matched by id so marks already entered survive an edit - renaming "Project" to
     * "Project Work" must not orphan the marks supervisors gave for it. A criterion the admin
     * dropped from the sheet is deleted, which is why edit() locks any that have been marked.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function saveCriteria(int $level, array $rows): void
    {
        DB::transaction(function () use ($level, $rows) {
            $keptIds = [];
            $position = 0;

            foreach ($rows as $row) {
                $label = trim((string) ($row['label'] ?? ''));

                if ($label === '') {
                    continue;
                }

                $attributes = [
                    'level' => $level,
                    'label' => $label,
                    'max_mark' => (float) ($row['max_mark'] ?? 0),
                    'position' => $position++,
                ];

                $existing = !empty($row['id'])
                    ? StsScoreCriterion::where('level', $level)->find($row['id'])
                    : null;

                if ($existing) {
                    $existing->update($attributes);
                    $keptIds[] = $existing->id;

                    continue;
                }

                $keptIds[] = StsScoreCriterion::create($attributes)->id;
            }

            StsScoreCriterion::where('level', $level)
                ->whereNotIn('id', $keptIds ?: [0])
                ->delete();
        });
    }

    /**
     * Shared validation for the criteria rows.
     */
    protected function rules(): array
    {
        return [
            'criteria' => 'required|array|min:1',
            'criteria.*.id' => 'nullable|integer|exists:sts_score_criteria,id',
            'criteria.*.label' => 'required|string|max:255',
            'criteria.*.max_mark' => 'required|numeric|min:0.01|max:999.99',
        ];
    }

    /**
     * Row-numbered validation messages - "criteria.3.label is required" tells an admin nothing.
     */
    protected function messages(): array
    {
        return [
            'criteria.required' => 'Add at least one scoring criterion.',
            'criteria.min' => 'Add at least one scoring criterion.',
            'criteria.*.label.required' => 'Every criterion needs a label.',
            'criteria.*.max_mark.required' => 'Every criterion needs a maximum mark.',
            'criteria.*.max_mark.min' => 'A criterion must be worth more than zero marks.',
            'level.unique' => 'That level already has a score sheet. Edit the existing one instead.',
        ];
    }
}
