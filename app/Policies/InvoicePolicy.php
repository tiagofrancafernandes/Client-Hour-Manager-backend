<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['invoice.view_any', 'invoice.view_own']);
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Can view any invoice
        if ($user->hasPermissionTo('invoice.view_any')) {
            return true;
        }

        // Can view own invoices (if user is linked to client)
        if ($user->hasPermissionTo('invoice.view_own')) {
            return $user->client_id === $invoice->client_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('invoice.create');
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Can update any invoice
        if ($user->hasPermissionTo('invoice.update_any')) {
            return true;
        }

        // Can update own invoices
        if ($user->hasPermissionTo('invoice.update_own')) {
            return $user->client_id === $invoice->client_id;
        }

        return false;
    }

    /**
     * Determine whether the user can mark invoice as paid.
     */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoice.mark_paid');
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoice.delete_any');
    }
}
