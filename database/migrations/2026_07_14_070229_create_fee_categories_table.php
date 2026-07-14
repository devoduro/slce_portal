<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            // The tuition category is special-cased in Student::applicableFeeStructure()'s
            // default parameter, which gates course-registration eligibility - protected
            // categories can't be deleted so that logic never silently breaks.
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        // Seed the categories that already exist as fee_structures/student_fee_charges
        // "category" string values, so nothing already stored becomes an orphaned value.
        DB::table('fee_categories')->insert([
            ['slug' => 'tuition', 'name' => 'Tuition / School Fees', 'is_protected' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'graduation', 'name' => 'Graduation Fee', 'is_protected' => false, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'resit', 'name' => 'Resit Fee', 'is_protected' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_categories');
    }
};
