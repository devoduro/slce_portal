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
        Schema::create('admission_photo_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->onDelete('cascade');
            $table->string('previous_photo_path')->nullable();
            $table->string('new_photo_path');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_photo_audits');
    }
};
