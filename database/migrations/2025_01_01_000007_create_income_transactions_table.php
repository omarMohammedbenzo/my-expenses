<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('income_source_id')->constrained()->cascadeOnDelete();
            $table->date('expected_date');
            $table->date('confirmed_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            // Account credited once confirmed (nullable until confirmation).
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['income_source_id', 'expected_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_transactions');
    }
};
