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
        Schema::create('admission_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_audit_logs');
    }
};
