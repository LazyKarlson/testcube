<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    /**
     * Get all roles (public endpoint for metadata)
     */
    public function metaRoles()
    {
        // Cache for 1 hour (3600 seconds)
        // Roles rarely change
        return Cache::remember('api:meta:roles', 3600, function () {
            $roles = Role::with('permissions')->get();

            return response()->json([
                'roles' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'permissions' => $role->permissions->pluck('name'),
                    ];
                }),
            ]);
        });
    }

    /**
     * Get all roles with their permissions (authenticated endpoint)
     */
    public function index()
    {
        // Share cache with metaRoles since data is identical
        return Cache::remember('api:meta:roles', 3600, function () {
            $roles = Role::with('permissions')->get();

            return response()->json([
                'roles' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'permissions' => $role->permissions->pluck('name'),
                    ];
                }),
            ]);
        });
    }

    /**
     * Assign role to user (admin only)
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->assignRole($validated['role']);

        // Invalidate user statistics cache (users by role changed)
        Cache::forget('api:stats:users');

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Remove role from user (admin only)
     */
    public function removeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->removeRole($validated['role']);

        // Invalidate user statistics cache (users by role changed)
        Cache::forget('api:stats:users');

        return response()->json([
            'message' => 'Role removed successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Get user's roles and permissions
     */
    public function getUserRoles(User $user)
    {
        $user->load('roles.permissions');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'name' => $role->name,
                        'description' => $role->description,
                        'permissions' => $role->permissions->pluck('name'),
                    ];
                }),
            ],
        ]);
    }
}
