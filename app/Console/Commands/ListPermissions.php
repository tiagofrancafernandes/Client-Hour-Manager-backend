<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ListPermissions extends Command
{
    protected $signature = 'permission:list {--role= : Filter by role name}';

    protected $description = 'List all permissions and roles in the system';

    public function handle(): int
    {
        $roleName = $this->option('role');

        if ($roleName) {
            return $this->showRolePermissions($roleName);
        }

        $this->showAllPermissions();
        $this->newLine();
        $this->showAllRoles();

        return Command::SUCCESS;
    }

    protected function showAllPermissions(): void
    {
        $permissions = Permission::orderBy('name')->get();

        $this->info('📋 All Permissions (' . $permissions->count() . ')');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $grouped = $permissions->groupBy(fn ($permission) => explode('.', $permission->name)[0]);

        foreach ($grouped as $resource => $perms) {
            $this->newLine();
            $this->warn(strtoupper($resource) . ' (' . $perms->count() . ')');

            foreach ($perms as $permission) {
                $this->line('  • ' . $permission->name);
            }
        }
    }

    protected function showAllRoles(): void
    {
        $roles = Role::withCount('permissions')->get();

        $this->info('🎭 Roles and Permission Counts');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $headers = ['Role', 'Permissions Count', 'Guard'];
        $rows = [];

        foreach ($roles as $role) {
            $rows[] = [
                $role->name,
                $role->permissions_count,
                $role->guard_name,
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->comment('💡 Tip: Use --role=admin to see specific role permissions');
    }

    protected function showRolePermissions(string $roleName): int
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            $this->error("Role '{$roleName}' not found.");

            $availableRoles = Role::pluck('name')->toArray();
            $this->info('Available roles: ' . implode(', ', $availableRoles));

            return Command::FAILURE;
        }

        $permissions = $role->permissions()->orderBy('name')->get();

        $this->info("🎭 Role: {$role->name}");
        $this->info("📋 Permissions: {$permissions->count()}");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $grouped = $permissions->groupBy(fn ($permission) => explode('.', $permission->name)[0]);

        foreach ($grouped as $resource => $perms) {
            $this->newLine();
            $this->warn(strtoupper($resource) . ' (' . $perms->count() . ')');

            foreach ($perms as $permission) {
                $this->line('  • ' . $permission->name);
            }
        }

        return Command::SUCCESS;
    }
}
