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
            // Informational overall capacity (e.g. a physical building limit), separate from
            // the per-level quotas that actually gate selection in availableQuota(). Defaults
            // to the sum of the level capacities when not explicitly set.
            $table->unsignedSmallInteger('total_capacity')->nullable()->after('capacity_level_400');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_schools', function (Blueprint $table) {
            $table->dropColumn('total_capacity');
        });
    }
};
