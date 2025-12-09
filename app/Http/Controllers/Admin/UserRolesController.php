<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRoleRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\UserRole;
use App\Services\RoleService;
use App\Services\UserRoleService;
use App\Traits\HasUserPermissions;
use Illuminate\Http\RedirectResponse;

class UserRolesController extends Controller
{
    use HasUserPermissions;

    public function __construct(
        private UserRoleService $userRoleService,
        private RoleService $roleService,
    ) {}

    public function store(StoreUserRoleRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        $userRole = $this->userRoleService->store($validatedData);

        $this->roleService->store($validatedData);

        return redirect()->back()
            ->with('success', __('alert.userRole.store.success', ['userRole' => $userRole->type]));
    }

    public function update($id, UpdateUserRoleRequest $request): RedirectResponse
    {
        $this->checkUserPermission('settings-roles-permissions', false);

        $userRole = UserRole::find($id);

        $validatedData = $request->validated();

        $this->roleService->update($validatedData, $userRole);

        $userRole = $this->userRoleService->update($validatedData, $userRole);

        return redirect()->back()
            ->with('success', __('alert.userRole.update.success', ['userRole' => $userRole->type]));
    }

    public function delete($id)
    {
        $this->checkUserPermission('settings-roles-permissions', false);

        $userRole = UserRole::find($id);

        $this->roleService->delete($userRole);

        $this->userRoleService->delete($userRole);

        return redirect()->back()
            ->with('success', __('alert.userRole.delete.success', ['userRole' => $userRole->type]));
    }

    public function getDataList(): array
    {
        return $this->userRoleService->getAll();
    }

    public function getPermissionList($id): array
    {
        $userRole = UserRole::find($id);

        return $this->roleService->getRolePermissions($userRole);
    }
}
