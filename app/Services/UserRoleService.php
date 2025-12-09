<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;

class UserRoleService
{
    use HandleTransactions;
    use ManageActivities;

    public function store(array $userRoleData, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($userRoleData, $user) {
            $userRole = UserRole::create($userRoleData);

            $this->log($userRole, 'userRoles.store', $user);

            return $userRole;
        });
    }

    public function update(array $userRoleData, UserRole $userRole, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($userRoleData, $userRole, $user) {
            $userRole->update($userRoleData);

            $this->log($userRole, 'userRoles.update', $user);

            return $userRole;
        });
    }

    public function delete(UserRole $userRole, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($userRole, $user) {
            $userRole->delete();

            $this->log($userRole, 'userRoles.delete', $user);

            return $userRole;
        });
    }

    public function getAll(): array
    {
        $exclude = [];

        return UserRole::whereNotIn('type', $exclude)
            ->get()
            ->map(function ($userRole) {
                $membersCount = User::where('user_role_id', $userRole->id)->count();

                return [
                    'id' => $userRole->id,
                    'name' => $userRole->type,
                    'membersCount' => $membersCount,
                ];
            })
            ->toArray();
    }
}
