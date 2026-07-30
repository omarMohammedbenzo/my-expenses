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
        Schema::table('gold_holdings', function (Blueprint $table) {
            $table->decimal('manufacturing_cost_per_gram', 10, 2)->unsigned()->default(0)->after('karat');
            $table->decimal('purchase_price', 12, 2)->unsigned()->nullable()->after('manufacturing_cost_per_gram');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gold_holdings', function (Blueprint $table) {
            $table->dropColumn(['manufacturing_cost_per_gram', 'purchase_price']);
        });
    }
};
