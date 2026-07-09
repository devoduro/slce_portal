<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_concurrent_classes')->default(1)->after('capacity');
        });

        // Preserve any venues that were already legitimately double (or triple, etc.) booked
        // under the old hardcoded "up to 2 concurrent" rule, by setting their capacity to
        // match whatever concurrency already exists in the data - computed, not hardcoded,
        // so this works regardless of which venues/entries actually exist.
        $entries = DB::table('timetable_entries')->whereNotNull('venue_id')->get();

        $maxConcurrentByVenue = [];

        foreach ($entries as $entry) {
            $concurrent = 1;

            foreach ($entries as $other) {
                if ($other->id === $entry->id) {
                    continue;
                }
                if ($other->venue_id !== $entry->venue_id
                    || $other->semester_id !== $entry->semester_id
                    || $other->day_of_week !== $entry->day_of_week) {
                    continue;
                }
                if ($other->start_time < $entry->end_time && $other->end_time > $entry->start_time) {
                    $concurrent++;
                }
            }

            $maxConcurrentByVenue[$entry->venue_id] = max($maxConcurrentByVenue[$entry->venue_id] ?? 1, $concurrent);
        }

        foreach ($maxConcurrentByVenue as $venueId => $count) {
            if ($count > 1) {
                DB::table('venues')->where('id', $venueId)->update(['max_concurrent_classes' => min($count, 255)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('max_concurrent_classes');
        });
    }
};
