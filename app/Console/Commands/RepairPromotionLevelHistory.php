<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\StudentLevelHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairPromotionLevelHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:repair-level-history {--apply : Write the corrections (otherwise this is a dry run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-file level-history snapshots that a promotion recorded against the year students moved INTO instead of the year they completed';

    /**
     * A promotion snapshots the level a student is leaving behind against the academic year they
     * studied it in. Run once the new academic year had already been made current, the old code
     * filed that snapshot against the new year instead - recording the exact opposite of the
     * truth: the OLD level for the year the student is studying at their NEW one, and no record
     * at all for the year they actually completed.
     *
     * Fee lookups then price that student's completed year off their post-promotion level, so
     * anyone carrying a balance has it recalculated at the wrong rate. This walks those rows back
     * to the year they belong to.
     *
     * A row is only touched when it is self-contradictory: it claims a student was at a level
     * during a year they demonstrably were not, because they have since been promoted past it and
     * there is no later year for that promotion to have happened in.
     */
    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $years = AcademicYear::orderBy('start_date')->get();

        if ($years->count() < 2) {
            $this->info('Fewer than two academic years exist - nothing could have been misfiled.');

            return self::SUCCESS;
        }

        $previousYearOf = [];

        foreach ($years as $index => $year) {
            if ($index > 0) {
                $previousYearOf[$year->id] = $years[$index - 1];
            }
        }

        // The snapshot says the student was BELOW their current level that year, so the promotion
        // that moved them up has to have happened afterwards. On the chronologically last
        // academic year there is no "afterwards" for it to have happened in, so the row can only
        // be on the wrong year.
        $latestYear = $years->last();

        $suspect = StudentLevelHistory::with('student')
            ->where('academic_year_id', $latestYear->id)
            ->whereHas('student', fn ($query) => $query->whereNotNull('level'))
            ->get()
            ->filter(fn (StudentLevelHistory $snapshot) => $snapshot->student
                && $snapshot->student->level !== null
                && (int) $snapshot->level < (int) $snapshot->student->level);

        if ($suspect->isEmpty()) {
            $this->info('No misfiled level-history snapshots found.');

            return self::SUCCESS;
        }

        $moves = [];
        $duplicates = [];
        $conflicts = [];
        $orphans = [];

        $existing = StudentLevelHistory::whereIn('student_id', $suspect->pluck('student_id'))
            ->get()
            ->groupBy('student_id');

        foreach ($suspect as $snapshot) {
            $target = $previousYearOf[$snapshot->academic_year_id] ?? null;

            if (!$target) {
                $orphans[] = $snapshot;
                continue;
            }

            $onTarget = ($existing[$snapshot->student_id] ?? collect())
                ->firstWhere('academic_year_id', $target->id);

            if (!$onTarget) {
                $moves[] = [$snapshot, $target];
            } elseif ((int) $onTarget->level === (int) $snapshot->level) {
                // The completed year already records the same level - the misfiled copy is
                // redundant and only does harm sitting on the year it does.
                $duplicates[] = [$snapshot, $target];
            } else {
                $conflicts[] = [$snapshot, $target, $onTarget];
            }
        }

        $this->newLine();
        $this->line(sprintf('Misfiled snapshots found: <options=bold>%d</>', $suspect->count()));

        foreach (collect($moves)->groupBy(fn ($move) => $move[0]->academic_year_id . '|' . $move[0]->level . '|' . $move[1]->id) as $group) {
            [$snapshot, $target] = $group->first();
            $from = $years->firstWhere('id', $snapshot->academic_year_id);

            $this->line(sprintf(
                '  Level %d: %d student(s) move from %s to %s',
                $snapshot->level,
                $group->count(),
                $from->name ?? $snapshot->academic_year_id,
                $target->name
            ));
        }

        if (!empty($duplicates)) {
            $this->line(sprintf('  %d redundant duplicate(s) will be removed.', count($duplicates)));
        }

        if (!empty($conflicts)) {
            $this->warn(sprintf('  %d row(s) skipped - the completed year already records a different level:', count($conflicts)));

            foreach (array_slice($conflicts, 0, 10) as [$snapshot, $target, $onTarget]) {
                $this->warn(sprintf(
                    '    Student #%d: misfiled Level %d vs Level %d already on %s',
                    $snapshot->student_id,
                    $snapshot->level,
                    $onTarget->level,
                    $target->name
                ));
            }
        }

        if (!empty($orphans)) {
            $this->warn(sprintf('  %d row(s) skipped - no earlier academic year to move them to.', count($orphans)));
        }

        if (!$apply) {
            $this->newLine();
            $this->info('Dry run - nothing written. Re-run with --apply to commit these corrections.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($moves, $duplicates) {
            foreach (collect($moves)->groupBy(fn ($move) => $move[1]->id) as $targetYearId => $group) {
                StudentLevelHistory::whereIn('id', collect($group)->map(fn ($move) => $move[0]->id))
                    ->update(['academic_year_id' => $targetYearId, 'updated_at' => now()]);
            }

            StudentLevelHistory::whereIn('id', collect($duplicates)->map(fn ($duplicate) => $duplicate[0]->id))->delete();
        });

        $this->newLine();
        $this->info(sprintf(
            'Repaired: %d snapshot(s) re-filed, %d duplicate(s) removed.',
            count($moves),
            count($duplicates)
        ));

        $this->line('Fee balances for the affected students now price their completed year at the level they actually studied it at.');

        return self::SUCCESS;
    }
}
