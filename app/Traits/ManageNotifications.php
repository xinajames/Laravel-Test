<?php

namespace App\Traits;

use App\Helpers\DateHelper;
use App\Services\ReminderInstanceService;

trait ManageNotifications
{
    private ?ReminderInstanceService $reminderInstanceService = null;

    private function getReminderInstanceService(): ReminderInstanceService
    {
        if (! $this->reminderInstanceService) {
            $this->reminderInstanceService = app(ReminderInstanceService::class);
        }

        return $this->reminderInstanceService;
    }

    public function getUserNotifications($user, $unreadOnly = false, $offset = null, $limit = null)
    {
        $notifications = $unreadOnly ? $user->unreadNotifications->skip($offset)->take($limit)
            : $user->notifications->skip($offset)->take($limit);

        return $notifications->map(
            function ($notification) {
                return [
                    'id' => $notification->id,
                    'header' => $notification->data['title'] ?? null,
                    'isInfo' => ! isset($notification->data['link']),
                    'isRead' => (bool) $notification->read_at,
                    'message' => $notification->data['message'] ?? null,
                    'date' => $notification->created_at->diffForHumans(),
                    'time' => DateHelper::changeDateFormat($notification->created_at, 'g:i A'),
                    'url' => $notification->data['link'] ?? null,
                    'data' => $notification->data['data'] ?? null,
                ];
            }
        );
    }

    public function getUnreadNotificationsCount($user)
    {
        return $user->unreadNotifications()->count();
    }

    public function getCombinedUnreadCount($user)
    {
        $unreadNotifications = $this->getUnreadNotificationsCount($user);
        $todayReminders = $this->getReminderInstanceService()->getTodayRemindersCount();

        return $unreadNotifications + $todayReminders;
    }

    public function markNotificationAsRead($user, $notification_id = null): void
    {
        if ($notification_id) {
            $user->unreadNotifications->where('id', $notification_id)->markAsRead();
        } else {
            $user->unreadNotifications->markAsRead();
        }
    }
}
