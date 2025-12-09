<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\UserRole;
use App\Services\RoleService;
use App\Traits\HasUserPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RolePermissionsController extends Controller
{
    use HasUserPermissions;

    public function __construct(
        private RoleService $roleService,
    ) {}

    public function update($id, Request $request): RedirectResponse
    {
        $this->checkUserPermission('settings-roles-permissions', false);

        $userRole = UserRole::find($id);

        $input = $request->all();
        foreach ($input['checkedPermissions'] as $permission) {
            Permission::findOrCreate($permission, 'tenant_web');
        }

        $this->roleService->syncPermissions($input['checkedPermissions'], $userRole, auth()->user());

        return redirect()->back()
            ->with('success', __('alert.permission.update.success', ['userRole' => $userRole->type]));
    }
}
