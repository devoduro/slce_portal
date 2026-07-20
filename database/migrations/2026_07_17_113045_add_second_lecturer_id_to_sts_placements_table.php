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
        Schema::table('sts_placements', function (Blueprint $table) {
            $table->foreignId('second_lecturer_id')->nullable()->after('lecturer_id')->constrained('lecturers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sts_placements', function (Blueprint $table) {
            $table->dropForeign(['second_lecturer_id']);
            $table->dropColumn('second_lecturer_id');
        });
    }
};
