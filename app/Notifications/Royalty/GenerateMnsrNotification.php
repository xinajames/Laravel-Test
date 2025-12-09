<?php

namespace App\Notifications\Royalty;

use App\Mail\Royalty\GenerateMnsrMail;
use App\Models\Royalty\MacroBatch;
use App\Notifications\BaseNotification;
use App\Traits\ManageNotificationData;
use App\Traits\ManageRecipientEmails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateMnsrNotification extends BaseNotification implements ShouldQueue
{
    use ManageNotificationData;
    use ManageRecipientEmails;
    use Queueable;

    private $batch_id;

    public function __construct($batch_id)
    {
        $this->batch_id = $batch_id;
    }

    protected array $defaultChannels = ['mail', 'database'];

    public function viaQueues(): array
    {
        return [
            'mail' => 'mail',
            'database' => 'in-app-notification',
        ];
    }

    public function toMail(object $notifiable)
    {
        $mailable = new GenerateMnsrMail($notifiable, $this->batch_id,
            __('notification.royalty.mnsr.mailSubject', ['batch_id' => $this->batch_id]),
            __('notification.royalty.mnsr.title'), __('notification.royalty.mnsr.message'));
        $mailable->to($this->getRecipientEmail($notifiable));

        return $mailable;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notification.royalty.mnsr.title', ['batch_id' => $this->batch_id]),
            'message' => __('notification.royalty.mnsr.message'),
            'link' => route('royalty'),
            'data' => [],
        ];
    }
}
