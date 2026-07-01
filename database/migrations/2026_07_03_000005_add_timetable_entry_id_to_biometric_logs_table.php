<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('biometric_logs', function (Blueprint $table) {
            $table->foreignId('timetable_entry_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biometric_logs', function (Blueprint $table) {
            $table->dropForeign(['timetable_entry_id']);
            $table->dropColumn('timetable_entry_id');
        });
    }
};
