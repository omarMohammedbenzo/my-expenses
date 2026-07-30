<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Default account credited when a transaction from this source is confirmed.
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->enum('frequency', ['monthly', 'weekly'])->default('monthly');
            // Day of month (1-31) for monthly frequency, or day of week (0-6, Sun-Sat) for weekly.
            $table->unsignedTinyInteger('expected_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_sources');
    }
};
