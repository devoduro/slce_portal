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
        Schema::table('timetable_entries', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->after('lecturer_id')->constrained('venues')->nullOnDelete();
        });

        // Backfill: turn each distinct existing free-text venue into a real
        // Venue row, then point the timetable entry at it.
        $now = now();
        $distinctVenues = DB::table('timetable_entries')
            ->whereNotNull('venue')
            ->where('venue', '!=', '')
            ->distinct()
            ->pluck('venue');

        foreach ($distinctVenues as $name) {
            $venueId = DB::table('venues')->where('name', $name)->value('id');

            if (!$venueId) {
                $venueId = DB::table('venues')->insertGetId([
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('timetable_entries')->where('venue', $name)->update(['venue_id' => $venueId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetable_entries', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropColumn('venue_id');
        });
    }
};
