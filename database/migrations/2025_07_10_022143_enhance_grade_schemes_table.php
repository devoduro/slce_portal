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
        Schema::table('grade_schemes', function (Blueprint $table) {
            // Remove old columns
            $table->dropUnique(['grade']);
            $table->dropColumn(['grade', 'min_score', 'max_score', 'grade_point', 'remark']);
            
            // Add new columns
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
        });

        // Create grades table for the many-to-one relationship
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scheme_id')->constrained()->onDelete('cascade');
            $table->string('grade', 5); // A, B+, B, etc.
            $table->decimal('min_score', 5, 2);
            $table->decimal('gpa_value', 3, 2);
            $table->timestamps();

            $table->unique(['grade_scheme_id', 'grade']);
            $table->index('min_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');

        Schema::table('grade_schemes', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['name', 'description', 'is_default']);
            
            // Add back old columns
            $table->string('grade');
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->decimal('grade_point', 3, 2);
            $table->string('remark');
            $table->unique('grade');
        });
    }
};
