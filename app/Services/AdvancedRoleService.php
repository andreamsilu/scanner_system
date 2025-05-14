<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdvancedRoleService
{
    protected $cacheTime = 3600; // 1 hour cache

    /**
     * Create a new role with permissions
     *
     * @param array $data
     * @param array $permissions
     * @return Role
     */
    public function createRole(array $data, array $permissions): Role
    {
        DB::beginTransaction();
        try {
            $role = Role::create($data);
            $role->permissions()->attach($permissions);
            
            $this->clearRoleCache();
            
            DB::commit();
            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update role permissions
     *
     * @param Role $role
     * @param array $permissions
     * @return Role
     */
    public function updateRolePermissions(Role $role, array $permissions): Role
    {
        DB::beginTransaction();
        try {
            $role->permissions()->sync($permissions);
            
            $this->clearRoleCache();
            
            DB::commit();
            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign role to user
     *
     * @param User $user
     * @param Role $role
     * @return User
     */
    public function assignRoleToUser(User $user, Role $role): User
    {
        DB::beginTransaction();
        try {
            $user->roles()->sync([$role->id]);
            
            $this->clearUserPermissionsCache($user);
            
            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if user has permission
     *
     * @param User $user
     * @param string $permission
     * @return bool
     */
    public function hasPermission(User $user, string $permission): bool
    {
        $cacheKey = "user_permissions_{$user->id}";
        
        $permissions = Cache::remember($cacheKey, $this->cacheTime, function () use ($user) {
            return $user->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('name')
                ->unique()
                ->toArray();
        });

        return in_array($permission, $permissions);
    }

    /**
     * Get all permissions for a user
     *
     * @param User $user
     * @return array
     */
    public function getUserPermissions(User $user): array
    {
        $cacheKey = "user_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($user) {
            return $user->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('name')
                ->unique()
                ->toArray();
        });
    }

    /**
     * Create custom permission
     *
     * @param array $data
     * @return Permission
     */
    public function createPermission(array $data): Permission
    {
        $permission = Permission::create($data);
        $this->clearRoleCache();
        return $permission;
    }

    /**
     * Get role hierarchy
     *
     * @return array
     */
    public function getRoleHierarchy(): array
    {
        $cacheKey = 'role_hierarchy';
        
        return Cache::remember($cacheKey, $this->cacheTime, function () {
            return Role::with('permissions')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions' => $role->permissions->pluck('name')
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Clear role cache
     */
    protected function clearRoleCache(): void
    {
        Cache::forget('role_hierarchy');
    }

    /**
     * Clear user permissions cache
     *
     * @param User $user
     */
    protected function clearUserPermissionsCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
    }
} 