<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $expenseCategory->user_id === null || $user->id === $expenseCategory->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->id === $expenseCategory->user_id;
    }

    public function delete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->id === $expenseCategory->user_id;
    }
}
