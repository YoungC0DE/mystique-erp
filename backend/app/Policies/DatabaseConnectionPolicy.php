<?php

namespace App\Policies;

use App\Models\DatabaseConnection;
use App\Models\User;

class DatabaseConnectionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_admin ? true : false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, DatabaseConnection $connection): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DatabaseConnection $connection): bool
    {
        return false;
    }

    public function delete(User $user, DatabaseConnection $connection): bool
    {
        return false;
    }
}
