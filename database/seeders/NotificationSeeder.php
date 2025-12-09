<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\TestNotifications;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@jfm.test')->first();

        for ($i = 1; $i <= 15; $i++) {
            if ($user) {
                $user->notify(new TestNotifications('Test Notification '.$i));
            }
        }

    }
}
