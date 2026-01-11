<?php

declare(strict_types=1);

return [
    'success' => 'Operation completed successfully.',
    'error' => 'An error occurred.',
    'not_found' => 'Resource not found.',
    'created' => ':resource created successfully.',
    'updated' => ':resource updated successfully.',
    'deleted' => ':resource deleted successfully.',
    'restored' => ':resource restored successfully.',
    'archived' => ':resource archived successfully.',

    // Client messages
    'client' => [
        'created' => 'Client created successfully.',
        'updated' => 'Client updated successfully.',
        'deleted' => 'Client deleted successfully.',
        'restored' => 'Client restored successfully.',
    ],

    // Wallet messages
    'wallet' => [
        'created' => 'Wallet created successfully.',
        'updated' => 'Wallet updated successfully.',
        'archived' => 'Wallet archived successfully.',
        'unarchived' => 'Wallet unarchived successfully.',
        'balance_calculated' => 'Balance calculated successfully.',
        'insufficient_balance' => 'Insufficient balance.',
        'transfer_completed' => 'Transfer completed successfully.',
        'transfer_failed' => 'Transfer failed.',
        'cannot_delete' => 'Wallets cannot be deleted.',
        'cannot_archive_default' => 'Default wallet cannot be archived.',
        'cannot_transfer_to_same' => 'Cannot transfer to the same wallet.',
        'default_wallet_name' => 'Default Wallet',
    ],

    // Transaction messages
    'transaction' => [
        'credit_added' => 'Credit added successfully.',
        'debit_added' => 'Debit added successfully.',
        'invalid_amount' => 'Amount must be greater than zero.',
        'immutable' => 'Transactions cannot be modified.',
    ],

    // Timer messages
    'timer' => [
        'started' => 'Timer started.',
        'paused' => 'Timer paused.',
        'resumed' => 'Timer resumed.',
        'stopped' => 'Timer stopped.',
        'cancelled' => 'Timer cancelled.',
        'invalid_state' => 'Invalid timer state.',
        'already_running' => 'Timer is already running.',
        'debit_description' => 'Time tracked',
    ],

    // Invoice messages
    'invoice' => [
        'created' => 'Invoice created successfully.',
        'paid' => 'Invoice marked as paid.',
        'marked_as_paid' => 'Invoice marked as paid successfully.',
        'cancelled' => 'Invoice cancelled.',
        'cannot_modify' => 'Paid invoices cannot be modified.',
        'cannot_cancel_paid' => 'Paid invoices cannot be cancelled.',
        'invalid_price' => 'Price per hour must be greater than zero.',
        'no_debt' => 'Wallet has no debt.',
        'payment_description' => 'Payment for invoice #:invoice_id',
        'payment_internal_note' => 'Invoice #:invoice_id payment received',
    ],

    // Package messages
    'package' => [
        'purchased' => 'Package purchased successfully.',
        'purchase_initiated' => 'Purchase initiated successfully.',
        'inactive' => 'This package is not available.',
        'purchase_disabled' => 'Purchases are disabled for this wallet.',
    ],

    // User messages
    'user' => [
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
        'cannot_delete_self' => 'You cannot delete your own account.',
        'roles_assigned' => 'Roles assigned successfully.',
    ],

    // Role messages
    'role' => [
        'created' => 'Role created successfully.',
        'updated' => 'Role updated successfully.',
        'deleted' => 'Role deleted successfully.',
        'cannot_delete_core' => 'Core roles (admin, staff, client) cannot be deleted.',
    ],
];
