<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * List all roles with their permissions.
     *
     * This endpoint is cached for 60 seconds to avoid database overhead.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = Cache::remember('api.roles.list', 60, function () {
            return Role::with('permissions:id,name')
                ->orderBy('name')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'permissions' => $role->permissions->pluck('name')->sort()->values(),
                        'permissions_count' => $role->permissions->count(),
                        'created_at' => $role->created_at?->toISOString(),
                    ];
                });
        });

        return response()->json([
            'data' => $roles,
            'meta' => [
                'total' => Role::count(),
                'cached' => true,
                'cache_ttl' => 60,
            ],
        ]);
    }

    /**
     * Show a specific role with its permissions.
     */
    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        $role->load('permissions:id,name');

        return response()->json([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
                'permissions_count' => $role->permissions->count(),
                'created_at' => $role->created_at?->toISOString(),
                'updated_at' => $role->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Clear cache
        Cache::forget('api.roles.list');

        $role->load('permissions:id,name');

        return response()->json([
            'message' => __('messages.role.created'),
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
                'permissions_count' => $role->permissions->count(),
            ],
        ], 201);
    }

    /**
     * Update a role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (isset($validated['name'])) {
            $role->update(['name' => $validated['name']]);
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Clear cache
        Cache::forget('api.roles.list');

        $role->load('permissions:id,name');

        return response()->json([
            'message' => __('messages.role.updated'),
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
                'permissions_count' => $role->permissions->count(),
            ],
        ]);
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        // Prevent deletion of core roles
        if (in_array($role->name, ['admin', 'staff', 'client'])) {
            return response()->json([
                'message' => __('messages.role.cannot_delete_core'),
            ], 422);
        }

        $role->delete();

        // Clear cache
        Cache::forget('api.roles.list');

        return response()->json([
            'message' => __('messages.role.deleted'),
        ]);
    }
}
