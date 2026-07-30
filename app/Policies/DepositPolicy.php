<?php

namespace App\Policies;

use App\Models\Deposit;
use App\Models\User;

class DepositPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deposit $deposit): bool
    {
        return $user->id === $deposit->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Deposit $deposit): bool
    {
        return $user->id === $deposit->user_id;
    }

    public function delete(User $user, Deposit $deposit): bool
    {
        return $user->id === $deposit->user_id;
    }
}
