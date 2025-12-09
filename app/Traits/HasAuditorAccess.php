<?php

namespace App\Traits;

use App\Models\StoreAuditor;
use App\Models\User;

trait HasAuditorAccess
{
    protected function checkAuditorAccess(User $user): bool
    {
        $allowedRoles = ['Store Auditor', 'Super Admin'];

        return $user->user_type_id === 1 && in_array($user->userRole?->name, $allowedRoles);
    }

    protected function hasStoreAuditorAccess(User $user, $storeId): bool
    {
        return StoreAuditor::where('store_id', $storeId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
