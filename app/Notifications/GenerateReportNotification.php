<?php

namespace App\Notifications;

use App\Enums\UserReportRequestStatusEnum;
use App\Mail\GenerateReportMail;
use App\Models\UserReportRequest;
use App\Traits\ManageNotificationData;
use App\Traits\ManageRecipientEmails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateReportNotification extends BaseNotification implements ShouldQueue
{
    use ManageNotificationData;
    use ManageRecipientEmails;
    use Queueable;

    protected array $defaultChannels = ['mail', 'database'];

    public function __construct(
        private UserReportRequest $userReportRequest,
        private $langData = null,
    ) {
        $this->langData = $this->getNotificationLangData('generateReports', $this->userReportRequest);
    }

    public function viaQueues(): array
    {
        return [
            'mail' => 'mail',
            'database' => 'in-app-notification',
        ];
    }

    public function toMail(object $notifiable)
    {
        $mailable = new GenerateReportMail($notifiable, $this->langData);

        $mailable->to($this->getRecipientEmail($notifiable));

        return $mailable;
    }

    public function toDatabase(object $notifiable)
    {
        $title = $this->userReportRequest->status == UserReportRequestStatusEnum::Successful()->value
            ? __('notification.generateReport.successful.title', ['reportName' => $this->langData['report_name']])
            : __('notification.generateReport.failed.title', ['reportName' => $this->langData['report_name']]);

        $message = $this->userReportRequest->status == UserReportRequestStatusEnum::Successful()->value
            ? __('notification.generateReport.successful.message')
            : __('notification.generateReport.failed.message');

        return [
            'title' => $title,
            'message' => $message,
            'link' => $this->langData['url'],
            'data' => [],
        ];
    }
}
