<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine whether the user can view any wallets.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['wallet.view_any', 'wallet.view_own']);
    }

    /**
     * Determine whether the user can view the wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        // Can view any wallet
        if ($user->hasPermissionTo('wallet.view_any')) {
            return true;
        }

        // Can view own wallets
        if ($user->hasPermissionTo('wallet.view_own')) {
            return $user->client_id === $wallet->client_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create wallets.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('wallet.create');
    }

    /**
     * Determine whether the user can update the wallet.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        // Can update any wallet
        if ($user->hasPermissionTo('wallet.update_any')) {
            return true;
        }

        // Can update own wallets
        if ($user->hasPermissionTo('wallet.update_own')) {
            return $user->client_id === $wallet->client_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the wallet.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        // Wallets cannot be deleted (business rule)
        // Only admins with explicit permission
        return $user->hasPermissionTo('wallet.delete_any');
    }

    /**
     * Determine whether the user can restore the wallet.
     */
    public function restore(User $user, Wallet $wallet): bool
    {
        return $user->hasPermissionTo('wallet.manage');
    }

    /**
     * Determine whether the user can permanently delete the wallet.
     */
    public function forceDelete(User $user, Wallet $wallet): bool
    {
        // Wallets cannot be force deleted (business rule)
        return false;
    }
}
