<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permission and Role Seeder
 *
 * This seeder creates a comprehensive permission system following classic CRUD ACL patterns.
 *
 * ## Permission Naming Convention
 *
 * Permissions follow the pattern: `resource.action`
 *
 * ### Standard CRUD Actions:
 * - `view_any` - View all records (admin-level access)
 * - `view_own` - View only own records (user-level access)
 * - `view` - View a specific record (requires ownership check in policies)
 * - `create` - Create new records
 * - `update_any` - Update any record (admin-level)
 * - `update_own` - Update only own records (user-level)
 * - `update` - Update specific record (requires ownership check)
 * - `delete_any` - Delete any record (admin-level)
 * - `delete_own` - Delete only own records (user-level)
 * - `delete` - Delete specific record (requires ownership check)
 *
 * ### Special Actions:
 * - `manage` - Full control over resource (create, read, update, delete all)
 * - `restore` - Restore soft-deleted records
 * - `force_delete` - Permanently delete records
 *
 * ## Roles
 *
 * ### admin
 * - Full access to all resources
 * - All permissions assigned
 * - Can manage users, roles, and permissions
 *
 * ### client
 * - Limited to own resources
 * - Can view and manage own wallets, transactions, timers
 * - Cannot see internal notes or admin-only features
 * - Can purchase packages
 *
 * ### staff
 * - Can manage clients and their resources
 * - Can create manual transactions
 * - Can see internal notes
 * - Cannot manage users or permissions
 *
 * @author Tiago França
 * @copyright (c) 2026
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        throw_unless(
            class_exists(Permission::class) && class_exists(Role::class),
            \Exception::class,
            'Spatie Permission package is not installed.'
        );

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ==========================================
        // USER PERMISSIONS
        // ==========================================
        $userPermissions = [
            'user.view_any',      // View all users (admin only)
            'user.view',          // View specific user
            'user.create',        // Create new users
            'user.update_any',    // Update any user (admin only)
            'user.update',        // Update specific user
            'user.delete_any',    // Delete any user (admin only)
            'user.delete',        // Delete specific user
            'user.manage',        // Full user management (admin only)
        ];

        // ==========================================
        // CLIENT PERMISSIONS
        // ==========================================
        $clientPermissions = [
            'client.view_any',    // View all clients
            'client.view_own',    // View own client profile
            'client.view',        // View specific client
            'client.create',      // Create new clients
            'client.update_any',  // Update any client
            'client.update_own',  // Update own client profile
            'client.update',      // Update specific client
            'client.delete_any',  // Delete any client
            'client.delete',      // Delete specific client
            'client.manage',      // Full client management
        ];

        // ==========================================
        // WALLET PERMISSIONS
        // ==========================================
        $walletPermissions = [
            'wallet.view_any',    // View all wallets
            'wallet.view_own',    // View own wallets
            'wallet.view',        // View specific wallet
            'wallet.create',      // Create new wallets
            'wallet.update_any',  // Update any wallet
            'wallet.update_own',  // Update own wallets
            'wallet.update',      // Update specific wallet
            'wallet.delete_any',  // Delete any wallet (admin only - wallets shouldn't be deleted)
            'wallet.delete',      // Delete specific wallet
            'wallet.manage',      // Full wallet management
            'wallet.archive',     // Archive wallets
        ];

        // ==========================================
        // TRANSACTION PERMISSIONS (Ledger)
        // ==========================================
        $transactionPermissions = [
            'transaction.view_any',           // View all transactions
            'transaction.view_own',           // View own wallet transactions
            'transaction.view',               // View specific transaction
            'transaction.create',             // Create manual transactions (staff/admin only)
            'transaction.view_internal_note', // View internal notes (staff/admin only)
            'transaction.manage',             // Full transaction management
        ];

        // ==========================================
        // TIMER PERMISSIONS
        // ==========================================
        $timerPermissions = [
            'timer.view_any',     // View all timers
            'timer.view_own',     // View own timers
            'timer.view',         // View specific timer
            'timer.create',       // Start new timer
            'timer.update_any',   // Update any timer (pause/resume/stop)
            'timer.update_own',   // Update own timers
            'timer.update',       // Update specific timer
            'timer.delete_any',   // Cancel any timer
            'timer.delete_own',   // Cancel own timers
            'timer.delete',       // Cancel specific timer
            'timer.manage',       // Full timer management
            'timer.view_hidden',  // View hidden timers (creator or admin)
        ];

        // ==========================================
        // INVOICE PERMISSIONS
        // ==========================================
        $invoicePermissions = [
            'invoice.view_any',   // View all invoices
            'invoice.view_own',   // View own invoices
            'invoice.view',       // View specific invoice
            'invoice.create',     // Create invoices
            'invoice.update_any', // Update any invoice
            'invoice.update_own', // Update own invoices
            'invoice.update',     // Update specific invoice
            'invoice.delete_any', // Delete any invoice
            'invoice.delete',     // Delete specific invoice
            'invoice.manage',     // Full invoice management
            'invoice.mark_paid',  // Mark invoice as paid
        ];

        // ==========================================
        // PACKAGE PERMISSIONS
        // ==========================================
        $packagePermissions = [
            'package.view_any',   // View all packages
            'package.view_own',   // View packages for own wallets
            'package.view',       // View specific package
            'package.create',     // Create new packages
            'package.update_any', // Update any package
            'package.update',     // Update specific package
            'package.delete_any', // Delete any package
            'package.delete',     // Delete specific package
            'package.manage',     // Full package management
            'package.purchase',   // Purchase packages (client)
        ];

        // ==========================================
        // TRANSFER PERMISSIONS
        // ==========================================
        $transferPermissions = [
            'transfer.create',       // Create wallet transfers
            'transfer.create_any',   // Transfer between any wallets (admin)
            'transfer.create_own',   // Transfer between own wallets
        ];

        // ==========================================
        // ROLE & PERMISSION MANAGEMENT
        // ==========================================
        $rolePermissions = [
            'role.view_any',      // View all roles
            'role.view',          // View specific role
            'role.create',        // Create new roles
            'role.update',        // Update roles
            'role.delete',        // Delete roles
            'role.manage',        // Full role management
        ];

        $permissionManagementPermissions = [
            'permission.view_any',  // View all permissions
            'permission.view',      // View specific permission
            'permission.create',    // Create new permissions
            'permission.update',    // Update permissions
            'permission.delete',    // Delete permissions
            'permission.manage',    // Full permission management
            'permission.assign',    // Assign permissions to roles/users
        ];

        // ==========================================
        // CLIENT-SPECIFIC PERMISSIONS
        // ==========================================
        $clientSpecificPermissions = [
            'client.access_portal',      // Access client portal
            'client.view_own_balance',   // View own wallet balance
            'client.view_own_history',   // View own transaction history
        ];

        // ==========================================
        // STAFF PERMISSIONS (Pre-defined set)
        // ==========================================
        $staffPermissions = [
            // Clients
            'client.view_any',
            'client.view',
            'client.create',
            'client.update_any',
            'client.manage',

            // Wallets
            'wallet.view_any',
            'wallet.view',
            'wallet.create',
            'wallet.update_any',
            'wallet.manage',
            'wallet.archive',

            // Transactions
            'transaction.view_any',
            'transaction.view',
            'transaction.create',
            'transaction.view_internal_note',
            'transaction.manage',

            // Timers
            'timer.view_any',
            'timer.view',
            'timer.create',
            'timer.update_any',
            'timer.delete_any',
            'timer.manage',
            'timer.view_hidden',

            // Invoices
            'invoice.view_any',
            'invoice.view',
            'invoice.create',
            'invoice.update_any',
            'invoice.manage',
            'invoice.mark_paid',

            // Packages
            'package.view_any',
            'package.view',
            'package.create',
            'package.update_any',
            'package.manage',

            // Transfers
            'transfer.create_any',
        ];

        // ==========================================
        // CLIENT PERMISSIONS (Pre-defined set)
        // ==========================================
        $clientRolePermissions = [
            // Client profile
            'client.view_own',
            'client.update_own',

            // Wallets
            'wallet.view_own',

            // Transactions
            'transaction.view_own',

            // Timers
            'timer.view_own',
            'timer.create',
            'timer.update_own',
            'timer.delete_own',

            // Invoices
            'invoice.view_own',

            // Packages
            'package.view_own',
            'package.purchase',

            // Transfers
            'transfer.create_own',

            // Client portal
            'client.access_portal',
            'client.view_own_balance',
            'client.view_own_history',
        ];

        // ==========================================
        // COMBINE ALL PERMISSIONS
        // ==========================================
        $permissions = array_unique([
            ...$userPermissions,
            ...$clientPermissions,
            ...$walletPermissions,
            ...$transactionPermissions,
            ...$timerPermissions,
            ...$invoicePermissions,
            ...$packagePermissions,
            ...$transferPermissions,
            ...$rolePermissions,
            ...$permissionManagementPermissions,
            ...$clientSpecificPermissions,
        ]);

        // ==========================================
        // CREATE PERMISSIONS
        // ==========================================
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // ==========================================
        // CREATE ROLES
        // ==========================================
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $client = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // ==========================================
        // ASSIGN PERMISSIONS TO ROLES
        // ==========================================

        // Admin has ALL permissions
        $admin->syncPermissions($permissions);

        // Staff has pre-defined staff permissions
        $staff->syncPermissions($staffPermissions);

        // Client has pre-defined client permissions
        $client->syncPermissions($clientRolePermissions);

        // ==========================================
        // OUTPUT SUMMARY
        // ==========================================
        $this->command->info('✅ Permissions and Roles created successfully!');
        $this->command->info('');
        $this->command->info("📊 Summary:");
        $this->command->info("   • Total Permissions: " . count($permissions));
        $this->command->info("   • Admin Permissions: " . count($permissions) . " (all)");
        $this->command->info("   • Staff Permissions: " . count($staffPermissions));
        $this->command->info("   • Client Permissions: " . count($clientRolePermissions));
        $this->command->info('');
        $this->command->info('🎭 Roles created:');
        $this->command->info('   • admin - Full system access');
        $this->command->info('   • staff - Manage clients and resources');
        $this->command->info('   • client - Limited to own resources');
    }
}
