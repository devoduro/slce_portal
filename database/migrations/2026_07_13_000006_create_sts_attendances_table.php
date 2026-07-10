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
        Schema::create('sts_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sts_placement_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->date('attendance_date');
            $table->foreignId('biometric_log_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['sts_placement_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_attendances');
    }
};
