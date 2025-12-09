<?php

namespace App\Notifications;

use App\Mail\ReminderDueMail;
use App\Models\ReminderInstance;
use App\Traits\ManageNotificationData;
use App\Traits\ManageRecipientEmails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReminderDueNotification extends BaseNotification implements ShouldQueue
{
    use ManageNotificationData;
    use ManageRecipientEmails;
    use Queueable;

    protected array $defaultChannels = ['mail', 'database'];

    public function __construct(
        private ReminderInstance $reminderInstance,
    ) {}

    public function viaQueues(): array
    {
        return [
            'mail' => 'mail',
            'database' => 'in-app-notification',
        ];
    }

    public function toMail(object $notifiable)
    {
        $langData = $this->getNotificationLangData('reminders', $this->reminderInstance);

        $mailable = new ReminderDueMail($notifiable, $langData);

        $mailable->to($this->getRecipientEmail($notifiable));

        return $mailable;
    }

    public function toDatabase(object $notifiable): array
    {
        $langData = $this->getNotificationLangData('reminders', $this->reminderInstance);

        return [
            'title' => __('notification.reminderDue.title'),
            'message' => __('notification.reminderDue.message', ['title' => $langData['title']]),
            'link' => $langData['url'],
            'data' => [],
        ];
    }
}
