<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\UserRole;
use App\Traits\HasUserPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserRoleSeeder extends Seeder
{
    use HasUserPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userRole = UserRole::create(['type' => 'Super Admin']);

        // Create Role & Permissions
        $role = Role::create([
            'name' => Str::slug($userRole->type),
            'guard_name' => 'web',
        ]);

        // Create permissions if non existent
        $allPermissions = $this->getPermissions([], 'permissions');
        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $editorPermissions = [
            'update-franchisees',
            'update-stores',
            'update-team',
            'update-royalty',
            'update-reports',
            'update-settings-roles-permissions',
            'update-settings-data-import',
            'update-stores-notifications-reminders',
            'update-settings-notifications',
        ];
        $role?->syncPermissions($editorPermissions);
    }
}
