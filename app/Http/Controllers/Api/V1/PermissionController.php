<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * List all permissions.
     *
     * This endpoint is cached for 60 seconds to avoid database overhead.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = Cache::remember('api.permissions.list', 60, function () {
            return Permission::query()
                ->orderBy('name')
                ->get()
                ->groupBy(function ($permission) {
                    // Group by resource (e.g., 'client' from 'client.view_any')
                    return explode('.', $permission->name)[0];
                })
                ->map(function ($group) {
                    return $group->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name,
                            'created_at' => $permission->created_at?->toISOString(),
                        ];
                    })->values();
                });
        });

        return response()->json([
            'data' => $permissions,
            'meta' => [
                'total' => Permission::count(),
                'cached' => true,
                'cache_ttl' => 60,
            ],
        ]);
    }
}
