<?php

namespace App\Notifications;

use App\Traits\ManageNotificationData;
use App\Traits\ManageRecipientEmails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DocumentImportNotification extends BaseNotification implements ShouldQueue
{
    use ManageNotificationData;
    use ManageRecipientEmails;
    use Queueable;

    protected array $defaultChannels = ['database'];

    public function __construct(
        private int $successCount,
        private array $errors = []
    ) {}

    public function viaQueues(): array
    {
        return [
            'database' => 'in-app-notification',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $hasErrors = ! empty($this->errors);

        if ($hasErrors && $this->successCount === 0) {
            $errorList = $this->formatErrorsAsBullets();

            return [
                'title' => 'Document Import Failed',
                'message' => 'The document import could not be completed. Please check the errors and try again.'.($errorList ? "\n\nErrors:\n".$errorList : ''),
                'link' => null,
                'data' => [
                    'success_count' => $this->successCount,
                    'errors' => $this->errors,
                ],
            ];
        }

        if ($hasErrors) {
            $errorList = $this->formatErrorsAsBullets();

            return [
                'title' => 'Document Import Completed with Errors',
                'message' => sprintf(
                    'Document import completed. %d documents processed successfully, but %d had errors.',
                    $this->successCount,
                    count($this->errors)
                ).($errorList ? "\n\nErrors:\n".$errorList : ''),
                'link' => null,
                'data' => [
                    'success_count' => $this->successCount,
                    'errors' => $this->errors,
                ],
            ];
        }

        return [
            'title' => 'Document Import Successful',
            'message' => sprintf(
                'Document import completed successfully. %d documents were processed.',
                $this->successCount
            ),
            'link' => null,
            'data' => [
                'success_count' => $this->successCount,
                'errors' => [],
            ],
        ];
    }

    private function formatErrorsAsBullets(): string
    {
        if (empty($this->errors)) {
            return '';
        }

        return implode("\n", array_map(function ($error) {
            return '• '.$error;
        }, $this->errors));
    }
}
