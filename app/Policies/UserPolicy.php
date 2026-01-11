<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('user.view_any');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.view_any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('user.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.update_any');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.delete_any');
    }
}
