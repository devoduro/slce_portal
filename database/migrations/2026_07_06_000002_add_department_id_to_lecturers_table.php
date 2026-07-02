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
        Schema::table('lecturers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('staff_id')->constrained('departments')->nullOnDelete();
        });

        // Backfill: turn each distinct existing free-text department into a real
        // Department row, then point the lecturer at it.
        $now = now();
        $distinctDepartments = DB::table('lecturers')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        foreach ($distinctDepartments as $name) {
            $departmentId = DB::table('departments')->where('name', $name)->value('id');

            if (!$departmentId) {
                $departmentId = DB::table('departments')->insertGetId([
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('lecturers')->where('department', $name)->update(['department_id' => $departmentId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
