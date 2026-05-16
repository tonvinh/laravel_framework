<?php

namespace App\Policies;

use App\Models\Demo;
use App\Models\User;

class DemoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Demo $demo): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Demo $demo): bool
    {
        return true;
    }

    public function delete(User $user, Demo $demo): bool
    {
        return true;
    }
}
