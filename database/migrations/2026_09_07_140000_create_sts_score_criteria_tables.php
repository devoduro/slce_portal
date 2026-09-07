<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the four hard-coded STS score components (attendance / project / assignment /
     * mid-semester) with criteria the college defines itself.
     *
     * Those four were baked into columns, so the labels on a supervisor's score sheet were
     * whatever a developer named them years ago - there was no way for the STS coordinator to
     * mark on "Lesson Delivery" or "Professional Conduct", or to run a different number of
     * criteria at different levels. Each criterion now carries its own label and maximum mark,
     * and supervisors' marks are stored against the criterion rather than a fixed column.
     */
    public function up(): void
    {
        Schema::create('sts_score_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('level');
            $table->string('label');
            $table->decimal('max_mark', 5, 2);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // One criterion of a given name per level - two "Lesson Delivery" rows on the same
            // score sheet would be indistinguishable to the supervisor marking it.
            $table->unique(['level', 'label']);
            $table->index(['level', 'position']);
        });

        Schema::create('sts_placement_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sts_placement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sts_score_criterion_id')->constrained('sts_score_criteria')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['sts_placement_id', 'sts_score_criterion_id'], 'sts_placement_scores_unique');
        });

        // The old fixed-column table is left in place but unused: it holds no rows in any
        // environment this ships to, and dropping a table is not something a migration should do
        // on the strength of that. Remove it in a later release once the new criteria are bedded in.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_placement_scores');
        Schema::dropIfExists('sts_score_criteria');
    }
};
