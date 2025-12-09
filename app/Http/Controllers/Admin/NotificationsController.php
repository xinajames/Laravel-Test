<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ManageNotifications;
use Illuminate\Http\RedirectResponse;

class NotificationsController extends Controller
{
    use ManageNotifications;

    public function getNotifications($lastIndex, $unread): array
    {
        $user = auth()->user();

        return array_values(
            $this->getUserNotifications($user, $unread === 'true', $lastIndex, 6)->toArray()
        );
    }

    public function getUnreadCount(): int
    {
        return $this->getUnreadNotificationsCount(auth()->user());
    }

    public function markAsRead($notification = null): RedirectResponse
    {
        $this->markNotificationAsRead(auth()->user(), $notification);

        if ($notification == null) {
            return redirect()->back()->with('success', 'Marked all as read');
        } else {
            // Dont include toast notification if single notification only
            return redirect()->back();
        }

    }
}
