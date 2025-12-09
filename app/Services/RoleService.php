<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Traits\HandleTransactions;
use App\Traits\HasUserPermissions;
use App\Traits\ManageActivities;
use Illuminate\Support\Str;

class RoleService
{
    use HandleTransactions;
    use HasUserPermissions;
    use ManageActivities;

    public function store(array $roleData, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($roleData) {
            $role = Role::create([
                'name' => Str::slug($roleData['type']),
                'guard_name' => 'web',
            ]);

            // Create permissions if non existent
            $allPermissions = $this->getPermissions([], 'permissions');
            foreach ($allPermissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            $role?->syncPermissions(['read-royalty']);

            return $role;
        });
    }

    public function update(array $roleData, UserRole $userRole, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($roleData, $userRole) {
            $role = Role::findByName(Str::slug($userRole->type), 'web');

            $role?->update(['name' => Str::slug($roleData['type'])]);

            return $role;
        });
    }

    public function delete(UserRole $userRole)
    {
        return $this->transact(function () use ($userRole) {
            $role = Role::findByName(Str::slug($userRole->type), 'web');
            $role?->delete();

            // Delete assign roles to all admin users
            User::where('user_role_id', $userRole->id)->get()->map(function ($user) {
                $user->assignRole([]);
            });

            return $userRole;
        });
    }

    public function getRolePermissions(UserRole $userRole): array
    {
        $role = Role::findByName(Str::slug($userRole->type), 'web');

        $permissions = $role->permissions->pluck('name')->toArray();

        return $this->getPermissions($permissions);
    }

    public function syncPermissions(array $permissionData, UserRole $userRole, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($permissionData, $userRole, $user) {
            $role = Role::findByName(Str::slug($userRole->type), 'web');

            $role?->syncPermissions($permissionData);

            $this->log($userRole, 'userRoles.permission.update', $user);

            return $role;
        });
    }
}
