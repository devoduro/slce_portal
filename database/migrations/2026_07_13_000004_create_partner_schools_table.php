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
        Schema::create('partner_schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->enum('category', ['early_grade', 'upper_primary', 'jhs']);
            $table->unsignedSmallInteger('capacity_level_100')->default(0);
            $table->unsignedSmallInteger('capacity_level_200')->default(0);
            $table->unsignedSmallInteger('capacity_level_300')->default(0);
            $table->unsignedSmallInteger('capacity_level_400')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_schools');
    }
};
