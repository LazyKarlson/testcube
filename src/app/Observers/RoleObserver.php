<?php

namespace App\Observers;

use App\Models\Role;
use Illuminate\Support\Facades\Cache;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->clearRolesCaches();
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        $this->clearRolesCaches();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $this->clearRolesCaches();
    }

    /**
     * Clear all roles-related caches
     */
    private function clearRolesCaches(): void
    {
        // Clear roles metadata cache
        Cache::forget('api:meta:roles');

        // Note: We don't clear user statistics here because:
        // - Role CRUD doesn't change user-role assignments
        // - User stats are cleared in RoleController when assigning/removing roles
    }
}
