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
        Schema::table('partner_schools', function (Blueprint $table) {
            // Nullable - separate from `category` (student eligibility) and distinct from
            // sts_placements.type (which is level-derived per student/term). This tags the
            // school itself as one an admin has designated for STS-level or Internship-level
            // placements. Existing rows are left null until an admin sets it via the edit form.
            $table->enum('type', ['sts', 'internship'])->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_schools', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
