<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('courses')->insert([
            [
                'code' => 'STS',
                'title' => 'Supported Teaching in Schools',
                'description' => 'Synthetic course used to record STS placement scores in Continuous Assessment.',
                'credit_hours' => 0,
                'semester_id' => null,
                'is_core' => true,
                'is_sts_course' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'INTERNSHIP',
                'title' => 'Internship',
                'description' => 'Synthetic course used to record Internship placement scores in Continuous Assessment.',
                'credit_hours' => 0,
                'semester_id' => null,
                'is_core' => true,
                'is_sts_course' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('courses')->whereIn('code', ['STS', 'INTERNSHIP'])->where('is_sts_course', true)->delete();
    }
};
