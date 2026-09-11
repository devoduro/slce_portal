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
        Schema::create('admission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('bank')->nullable();
            // Bank/MoMo transaction reference - distinct from the admission's own institutional
            // reference_number.
            $table->string('reference_number')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status')->default('recorded');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_payments');
    }
};
