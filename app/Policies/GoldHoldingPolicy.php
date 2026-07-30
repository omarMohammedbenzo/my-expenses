<?php

namespace App\Policies;

use App\Models\GoldHolding;
use App\Models\User;

class GoldHoldingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GoldHolding $goldHolding): bool
    {
        return $user->id === $goldHolding->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, GoldHolding $goldHolding): bool
    {
        return $user->id === $goldHolding->user_id;
    }

    public function delete(User $user, GoldHolding $goldHolding): bool
    {
        return $user->id === $goldHolding->user_id;
    }
}
