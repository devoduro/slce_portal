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
        Schema::table('admissions', function (Blueprint $table) {
            // Present in the institution's real bulk admission list (Mr/Mrs/Miss) but not on
            // the Student table - display-only, dropped when migrating to Student.
            $table->string('title', 20)->nullable()->after('full_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
