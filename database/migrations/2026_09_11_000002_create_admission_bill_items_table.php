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
        Schema::create('admission_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->onDelete('cascade');
            // A FeeCategory slug - the same lookup table StudentFeeCharge.category reuses.
            $table->string('category');
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('payment_deadline')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_bill_items');
    }
};
