<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Timer;
use App\Models\User;

class TimerPolicy
{
    /**
     * Determine whether the user can view any timers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['timer.view_any', 'timer.view_own']);
    }

    /**
     * Determine whether the user can view the timer.
     */
    public function view(User $user, Timer $timer): bool
    {
        // Can view any timer
        if ($user->hasPermissionTo('timer.view_any')) {
            return true;
        }

        // Can view own timers
        if ($user->hasPermissionTo('timer.view_own')) {
            return $timer->started_by_id === $user->id;
        }

        // Can view hidden timers (if owns the timer)
        if ($user->hasPermissionTo('timer.view_hidden')) {
            return $timer->started_by_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create timers.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('timer.create');
    }

    /**
     * Determine whether the user can update the timer.
     */
    public function update(User $user, Timer $timer): bool
    {
        // Can update any timer
        if ($user->hasPermissionTo('timer.update_any')) {
            return true;
        }

        // Can update own timers
        if ($user->hasPermissionTo('timer.update_own')) {
            return $timer->started_by_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the timer.
     */
    public function delete(User $user, Timer $timer): bool
    {
        // Can delete any timer
        if ($user->hasPermissionTo('timer.delete_any')) {
            return true;
        }

        // Can delete own timers
        if ($user->hasPermissionTo('timer.delete_own')) {
            return $timer->started_by_id === $user->id;
        }

        return false;
    }
}
