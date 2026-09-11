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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_number')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('programme_id')->constrained('programmes')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->unsignedInteger('level')->default(100);
            $table->string('hall')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('hometown')->nullable();
            $table->string('gps_address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // The institution's own externally-assigned number - entered by staff, not
            // system-generated. Matches students.reference_number's varchar(7) exactly, since
            // it is copied straight into that column (as both reference_number and index_number)
            // at migration time.
            $table->string('reference_number', 7)->nullable()->unique();

            $table->string('passport_photo')->nullable();

            $table->string('admission_status')->default('offered');
            $table->string('payment_status')->default('not_billed');

            $table->timestamp('documents_verified_at')->nullable();
            $table->timestamp('profile_confirmed_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('migrated_at')->nullable();

            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
