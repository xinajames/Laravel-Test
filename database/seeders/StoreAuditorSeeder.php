<?php

namespace Database\Seeders;

use App\Enums\UserStatusEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreAuditor;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StoreAuditorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userRole = UserRole::firstOrCreate(['type' => 'Store Auditor']);

        // Create Role & Assign Permissions
        $role = Role::firstOrCreate([
            'name' => Str::slug($userRole->type), // e.g., 'store-auditor'
            'guard_name' => 'web',
        ]);

        $auditorPermissions = [
            'read-stores',
            'update-stores',
            'update-store-auditor',
        ];

        foreach ($auditorPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role->syncPermissions($auditorPermissions);

        $auditors = collect();

        for ($i = 1; $i <= 3; $i++) {
            $email = $i === 1 ? 'auditor@jfm.test' : "auditor{$i}@jfm.test";

            $auditor = User::factory()->create([
                'name' => 'Test'.($i).' Auditor',
                'email' => $email,
                'password' => Hash::make('pass'),
                'user_type_id' => 1,
                'user_role_id' => $userRole->id,
                'status' => UserStatusEnum::Active()->value,
            ]);

            $auditor->assignRole($role->name);
            $auditors->push($auditor);
        }

        // Assign one random auditor to each store
        foreach (Store::all() as $store) {
            $randomAuditor = $auditors->random();

            StoreAuditor::firstOrCreate([
                'store_id' => $store->id,
                'user_id' => $randomAuditor->id,
            ]);
        }
    }
}
