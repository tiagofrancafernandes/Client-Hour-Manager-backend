<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine whether the authenticatable can view any wallets.
     */
    public function viewAny(User|Client $authenticatable): bool
    {
        // Admin can see all wallets
        if ($authenticatable instanceof User && $authenticatable->hasRole('admin')) {
            return true;
        }

        // Clients can see wallets (filtered by their own in the query)
        return $authenticatable instanceof Client;
    }

    /**
     * Determine whether the authenticatable can view the wallet.
     */
    public function view(User|Client $authenticatable, Wallet $wallet): bool
    {
        // Admin can see all wallets
        if ($authenticatable instanceof User && $authenticatable->hasRole('admin')) {
            return true;
        }

        // Client can only see their own wallets
        if ($authenticatable instanceof Client) {
            return $wallet->client_id === $authenticatable->id;
        }

        return false;
    }

    /**
     * Determine whether the authenticatable can create wallets.
     */
    public function create(User|Client $authenticatable): bool
    {
        // Only admin can create wallets
        return $authenticatable instanceof User && $authenticatable->hasRole('admin');
    }

    /**
     * Determine whether the authenticatable can update the wallet.
     */
    public function update(User|Client $authenticatable, Wallet $wallet): bool
    {
        // Admin can update any wallet
        if ($authenticatable instanceof User && $authenticatable->hasRole('admin')) {
            return true;
        }

        // Client cannot update wallets (business rule)
        return false;
    }

    /**
     * Determine whether the authenticatable can delete the wallet.
     */
    public function delete(User|Client $authenticatable, Wallet $wallet): bool
    {
        // Wallets cannot be deleted (business rule)
        return false;
    }

    /**
     * Determine whether the authenticatable can restore the wallet.
     */
    public function restore(User|Client $authenticatable, Wallet $wallet): bool
    {
        // Only admin can restore wallets
        return $authenticatable instanceof User && $authenticatable->hasRole('admin');
    }

    /**
     * Determine whether the authenticatable can permanently delete the wallet.
     */
    public function forceDelete(User|Client $authenticatable, Wallet $wallet): bool
    {
        // Wallets cannot be force deleted (business rule)
        return false;
    }
}
