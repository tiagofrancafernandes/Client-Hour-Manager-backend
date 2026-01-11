<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('client.view_any');
    }

    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        // Can view any client
        if ($user->hasPermissionTo('client.view_any')) {
            return true;
        }

        // Can view own client (if user is linked to client)
        if ($user->hasPermissionTo('client.view_own')) {
            return $user->client_id === $client->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('client.create');
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        // Can update any client
        if ($user->hasPermissionTo('client.update_any')) {
            return true;
        }

        // Can update own client
        if ($user->hasPermissionTo('client.update_own')) {
            return $user->client_id === $client->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        // Can delete any client
        if ($user->hasPermissionTo('client.delete_any')) {
            return true;
        }

        // Can delete specific client
        if ($user->hasPermissionTo('client.delete')) {
            // Additional business logic can go here
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the client.
     */
    public function restore(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('client.manage');
    }

    /**
     * Determine whether the user can permanently delete the client.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('client.manage');
    }
}
