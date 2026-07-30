<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Food', 'icon' => '🍔'],
            ['name' => 'Drinks', 'icon' => '☕'],
            ['name' => 'Going Out / Entertainment', 'icon' => '🎉'],
            ['name' => 'Family Expenses', 'icon' => '👨‍👩‍👧'],
            ['name' => 'Rent', 'icon' => '🏠'],
            ['name' => 'Clothes', 'icon' => '👕'],
            ['name' => 'Subscriptions', 'icon' => '📺'],
            ['name' => 'Transportation', 'icon' => '🚗'],
            ['name' => 'Health', 'icon' => '💊'],
            ['name' => 'Groceries', 'icon' => '🛒'],
            ['name' => 'Bills & Utilities', 'icon' => '💡'],
            ['name' => 'Other', 'icon' => '📦'],
        ];

        foreach ($defaults as $category) {
            ExpenseCategory::firstOrCreate([
                'user_id' => null,
                'name' => $category['name'],
            ], $category);
        }
    }
}
