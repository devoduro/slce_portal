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
        Schema::create('admission_letter_templates', function (Blueprint $table) {
            $table->id();
            // One customizable letter body per academic year - covers both the
            // provisional (pre-payment-confirmation) and final (post-approval) letter,
            // since AdmissionController::buildLetterPdf() already branches on $isFinal.
            $table->foreignId('academic_year_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('provisional_body')->nullable();
            $table->text('final_body')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_letter_templates');
    }
};
