<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give each STS term its own quota per school and level, instead of one set of numbers on the
     * school row that every term has to share.
     *
     * A school's allocation is not a permanent property of the school - it is what that school
     * agreed to take for a particular batch. Holding it in capacity_level_100..400 means last
     * year's agreement and this year's are the same four numbers, so a cohort moving up a level
     * has nowhere to move its allocation to: the slots those students still occupy stay recorded
     * against the level they have left. With a row per term, the batch that was 9 places at
     * Level 300 last year becomes 9 places at Level 400 this year, and last year's record stays
     * intact behind it.
     *
     * The old columns stay as the fallback for any school/term with no row here, so nothing has
     * to be migrated up front and an untouched school keeps behaving exactly as it does today.
     */
    public function up(): void
    {
        Schema::create('partner_school_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sts_term_id')->constrained('sts_terms')->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->timestamps();

            $table->unique(['partner_school_id', 'sts_term_id', 'level'], 'partner_school_quotas_unique');
            $table->index(['sts_term_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_school_quotas');
    }
};
