<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasUserPermissions
{
    public function getModulePermissions($module = null): array
    {
        $user = auth()->user();

        $permissions_arr = $user->getAllPermissions()->pluck('name')->toArray();
        if (! $module) {
            return array_values($permissions_arr);
        }

        return array_values(preg_grep('/'.$module.'/i', $permissions_arr));
    }

    public function getPermissions($tenant_permissions, $retrieve = 'modules'): array
    {
        $modules = [
            [
                'name' => 'Franchisees',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-franchisees',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-franchisees',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Stores',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-stores',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-stores',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Stores > Notification & Reminders',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-stores-notifications-reminders',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-stores-notifications-reminders',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Team',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-team',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-team',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Royalty',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-royalty',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-royalty',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Reports',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-reports',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-reports',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Settings > Roles & Permissions',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-settings-roles-permissions',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-settings-roles-permissions',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Settings > Data Import',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-settings-data-import',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-settings-data-import',
                        'checked' => false,
                    ],
                ],
            ],
            [
                'name' => 'Settings > Notifications',
                'permissions' => [
                    [
                        'name' => 'View Only',
                        'slug' => 'read-settings-notifications',
                        'checked' => false,
                    ],
                    [
                        'name' => 'Editor',
                        'slug' => 'update-settings-notifications',
                        'checked' => false,
                    ],
                ],
            ],
        ];

        if ($retrieve === 'modules') {
            // Loop into modules, apply checked state if permission is found on role
            foreach ($modules as &$module) {
                foreach ($module['permissions'] as &$permission) {
                    $permission['checked'] = in_array($permission['slug'], $tenant_permissions);
                }
            }

            return $modules;
        }

        if ($retrieve === 'permissions') {
            // Loop into modules, retrieve all slugs as permissions
            $permissions = [];
            foreach ($modules as $module) {
                foreach ($module['permissions'] as $permission) {
                    $permissions[] = $permission['slug'];
                }
            }

            return $permissions;
        }

        return [];
    }

    public function checkUserPermission(string $module, bool $checkRead = true, bool $checkUpdate = true): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized');
        }

        // If no permission checks are needed, return early
        if (! $checkRead && ! $checkUpdate) {
            return;
        }

        // If both checks are enabled, require both permissions
        if ($checkRead && $checkUpdate) {
            if (! $user->hasPermissionTo("read-{$module}") && ! $user->hasPermissionTo("update-{$module}")) {
                abort(403, 'Forbidden');
            }

            return;
        }

        // If only read is required
        if ($checkRead && ! $user->hasPermissionTo("read-{$module}")) {
            abort(403, 'Forbidden');
        }

        // If only update is required
        if ($checkUpdate && ! $user->hasPermissionTo("update-{$module}")) {
            abort(403, 'Forbidden');
        }
    }
}
