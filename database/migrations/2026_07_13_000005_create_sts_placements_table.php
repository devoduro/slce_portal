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
        Schema::create('sts_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('sts_term_id')->constrained()->onDelete('cascade');
            $table->foreignId('partner_school_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('lecturer_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedSmallInteger('level');
            $table->enum('type', ['sts', 'internship']);
            $table->timestamp('selected_at')->nullable();
            $table->timestamp('supervisor_assigned_at')->nullable();
            $table->timestamp('letter_printed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'sts_term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_placements');
    }
};
