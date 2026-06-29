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
        Schema::table('students', function (Blueprint $table) {
            $table->string('hometown')->nullable();
            $table->string('gps_address')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('hometown');
            $table->dropColumn('gps_address');
            $table->dropColumn('emergency_contact_relationship');
        });
    }
};
