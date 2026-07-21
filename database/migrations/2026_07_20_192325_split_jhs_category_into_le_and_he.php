<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Splits the single 'jhs' category into 'jhs_le' (Languages) and 'jhs_he' (Home
     * Economics) on both programmes.sts_category and partner_schools.category. MySQL enum
     * columns only accept values already in their definition, so each column is widened to
     * include both old and new values, existing 'jhs' rows are reclassified, then the enum is
     * narrowed to drop 'jhs' entirely.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE programmes MODIFY sts_category ENUM('early_grade','upper_primary','jhs','jhs_le','jhs_he') NULL");
        DB::statement("ALTER TABLE partner_schools MODIFY category ENUM('early_grade','upper_primary','jhs','jhs_le','jhs_he') NOT NULL");

        // Programmes: LE/HE are known exactly by their programme code.
        DB::table('programmes')->where('sts_category', 'jhs')->where('code', 'LE')->update(['sts_category' => 'jhs_le']);
        DB::table('programmes')->where('sts_category', 'jhs')->where('code', 'HE')->update(['sts_category' => 'jhs_he']);
        // Any other programme still on 'jhs' (shouldn't exist, but just in case) - default to LE.
        DB::table('programmes')->where('sts_category', 'jhs')->update(['sts_category' => 'jhs_le']);

        // Partner schools: no reliable signal to split by name, so per admin instruction all
        // existing 'jhs' schools default to 'jhs_le' - flagged for manual review afterward.
        DB::table('partner_schools')->where('category', 'jhs')->update(['category' => 'jhs_le']);

        DB::statement("ALTER TABLE programmes MODIFY sts_category ENUM('early_grade','upper_primary','jhs_le','jhs_he') NULL");
        DB::statement("ALTER TABLE partner_schools MODIFY category ENUM('early_grade','upper_primary','jhs_le','jhs_he') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE programmes MODIFY sts_category ENUM('early_grade','upper_primary','jhs','jhs_le','jhs_he') NULL");
        DB::statement("ALTER TABLE partner_schools MODIFY category ENUM('early_grade','upper_primary','jhs','jhs_le','jhs_he') NOT NULL");

        DB::table('programmes')->whereIn('sts_category', ['jhs_le', 'jhs_he'])->update(['sts_category' => 'jhs']);
        DB::table('partner_schools')->whereIn('category', ['jhs_le', 'jhs_he'])->update(['category' => 'jhs']);

        DB::statement("ALTER TABLE programmes MODIFY sts_category ENUM('early_grade','upper_primary','jhs') NULL");
        DB::statement("ALTER TABLE partner_schools MODIFY category ENUM('early_grade','upper_primary','jhs') NOT NULL");
    }
};
