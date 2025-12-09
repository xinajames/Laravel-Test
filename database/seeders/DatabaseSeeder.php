<?php

namespace Database\Seeders;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $userType = UserType::create(['name' => 'Admin']);

        $this->call([
            UserRoleSeeder::class,
        ]);

        if (app()->environment('production') || app()->environment('uat')) {
            // Production environment: Create only 1 super admin
            $superAdmin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@jfm.com',
                'email_verified_at' => now(),
                'password' => Hash::make('SecurePassword123!'),
                'password_updated_at' => now(),
                'user_type_id' => $userType->id,
                'user_role_id' => 1, // super-admin
                'status' => UserStatusEnum::Active()->value,
            ]);

            $superAdmin->assignRole('super-admin');

            $this->command->info('Super Admin created for production environment.');

            // Production seeders
            $this->call([
                QuestionnaireSeeder::class,
                MacroFileTypeAndRevisionSeeder::class,
                MacroFixedCacheSeeder::class,
                SalesPerformanceSeeder::class,
            ]);

            // Trigger add-global-reminders command for production
            $this->command->info('Running stores:add-global-reminders command...');
            Artisan::call('stores:add-global-reminders');
            $this->command->info('Global reminders added successfully.');

        } else {
            // Non-production environment: Create multiple test users
            $users = User::factory()->count(5)->sequence(
                fn ($sequence) => [
                    'name' => 'Test'.($sequence->index + 1).' Admin',
                    'email' => $sequence->index === 0
                        ? 'admin@jfm.test'
                        : 'admin'.($sequence->index + 1).'@jfm.test',
                    'password' => Hash::make('pass'),
                    'user_type_id' => $userType->id,
                    'user_role_id' => 1, // super-admin
                    'status' => UserStatusEnum::Active()->value,
                ]
            )->create();

            foreach ($users as $user) {
                $user->assignRole('super-admin');
            }

            // Non-production seeders
            $this->call([
                FranchiseeSeeder::class,
                StoreSeeder::class,
                QuestionnaireSeeder::class,
                StoreAuditorSeeder::class,
                ReminderSeeder::class,
                // NotificationSeeder::class,
                MacroFileTypeAndRevisionSeeder::class,
                MacroFixedCacheSeeder::class,
                SalesPerformanceSeeder::class,
            ]);

            // Trigger add-global-reminders command for non-production as well
            $this->command->info('Running stores:add-global-reminders command...');
            Artisan::call('stores:add-global-reminders');
            $this->command->info('Global reminders added successfully.');
        }
    }
}
