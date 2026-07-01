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
        Schema::create('ca_score_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('level')->unique();
            $table->decimal('attendance_max', 5, 2);
            $table->decimal('project_max', 5, 2);
            $table->decimal('assignment_max', 5, 2);
            $table->decimal('mid_semester_max', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ca_score_settings');
    }
};
