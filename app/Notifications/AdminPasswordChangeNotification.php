<?php

namespace App\Notifications;

use App\Mail\AdminPasswordChangeMail;
use App\Traits\ManageNotificationData;
use App\Traits\ManageRecipientEmails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminPasswordChangeNotification extends BaseNotification implements ShouldQueue
{
    use ManageNotificationData;
    use ManageRecipientEmails;
    use Queueable;

    protected array $defaultChannels = ['mail'];

    public function __construct(
        private $generatedPassword,
    ) {}

    public function viaQueues(): array
    {
        return [
            'mail' => 'mail-high-priority',
            // 'database' => 'in-app-notification',
        ];
    }

    public function toMail(object $notifiable)
    {
        $langData = $this->getNotificationLangData(null, null);

        $mailable = new AdminPasswordChangeMail($notifiable, $this->generatedPassword, $langData);

        $mailable->to($this->getRecipientEmail($notifiable));

        return $mailable;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notification.adminPasswordChange.title'),
            'message' => __('notification.adminPasswordChange.message'),
            'data' => [],
        ];
    }
}
