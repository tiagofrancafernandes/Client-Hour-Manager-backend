<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\HourTransaction;
use App\Models\User;

class HourTransactionPolicy
{
    /**
     * Determine whether the authenticatable can view any transactions.
     */
    public function viewAny(User|Client $authenticatable): bool
    {
        // Admin can see all transactions
        if ($authenticatable instanceof User && $authenticatable->hasRole('admin')) {
            return true;
        }

        // Clients can see transactions (filtered by their wallets in the query)
        return $authenticatable instanceof Client;
    }

    /**
     * Determine whether the authenticatable can view the transaction.
     */
    public function view(User|Client $authenticatable, HourTransaction $transaction): bool
    {
        // Admin can see all transactions
        if ($authenticatable instanceof User && $authenticatable->hasRole('admin')) {
            return true;
        }

        // Client can only see transactions from their own wallets
        if ($authenticatable instanceof Client) {
            return $transaction->wallet->client_id === $authenticatable->id;
        }

        return false;
    }

    /**
     * Determine whether the authenticatable can create transactions.
     */
    public function create(User|Client $authenticatable): bool
    {
        // Only admin can manually create transactions
        return $authenticatable instanceof User && $authenticatable->hasRole('admin');
    }

    /**
     * Determine whether the authenticatable can update the transaction.
     */
    public function update(User|Client $authenticatable, HourTransaction $transaction): bool
    {
        // Transactions are immutable (business rule)
        return false;
    }

    /**
     * Determine whether the authenticatable can delete the transaction.
     */
    public function delete(User|Client $authenticatable, HourTransaction $transaction): bool
    {
        // Transactions cannot be deleted (business rule - ledger is append-only)
        return false;
    }

    /**
     * Determine whether the authenticatable can see internal notes.
     */
    public function viewInternalNote(User|Client $authenticatable, HourTransaction $transaction): bool
    {
        // Only admin can see internal notes
        return $authenticatable instanceof User && $authenticatable->hasRole('admin');
    }
}
