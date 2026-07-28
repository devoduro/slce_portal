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
        foreach (['lesson_attendances', 'sts_attendances'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('source')->default('biometric')->after('biometric_log_id');
                $table->foreignId('recorded_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['lesson_attendances', 'sts_attendances'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('recorded_by');
                $table->dropColumn('source');
            });
        }
    }
};
