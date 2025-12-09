<?php

namespace App\Services;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationService
{
    public static function handleNotification(User $user, $notification, $activeOnly = true): void
    {
        // Notify only active users
        if ($activeOnly) {
            if (! $user->status == UserStatusEnum::Active()->value) {
                return;
            }
        }

        // $channels = [];
        // Dynamically set the channels and send the notification
        // $notification->setDynamicChannels($channels);

        $user->notify($notification);
    }

    public static function getUserAdmins($adminRole)
    {
        $formattedRoleType = Str::of($adminRole)->replace('-', ' ')->title();

        return User::with('roles')
            ->whereHas('userRole', function ($query) use ($formattedRoleType) {
                $query->where('type', $formattedRoleType);
            })
            ->where('user_type_id', UserTypeEnum::Admin()->value)
            ->where('status', UserStatusEnum::Active()->value)
            ->limit(1) // TODO :: Remove, used for testing only
            ->get();
    }
}
