<?php

namespace App\Traits;

use App\Enums\UserReportRequestStatusEnum;
use App\Helpers\DateHelper;
use Carbon\Carbon;

trait ManageNotificationData
{
    use ManageFilesystems;

    public function getToDatabaseData(): array
    {
        return [

        ];
    }

    public function getNotificationLangData($notification, $modelObject): array
    {
        if (empty($notification) || empty($modelObject)) {
            return [];
        }

        return match ($notification) {
            // Reminders
            'reminders' => [
                'title' => $modelObject->title,
                'description' => $modelObject->description,
                'scheduled_at' => DateHelper::changeDateFormat($modelObject->scheduled_at, 'F j, Y'),
                'reference_date' => $modelObject->reference_date_field
                && $modelObject->remindable
                && isset($modelObject->remindable->{$modelObject->reference_date_field})
                    ? DateHelper::changeDateFormat(
                        Carbon::parse($modelObject->remindable->{$modelObject->reference_date_field}),
                        'F j, Y'
                    )
                    : null,
                'store_name' => $modelObject->remindable->jbs_name ?? 'N/A',
                'days_before' => $modelObject->days_before,
                'is_manual' => is_null($modelObject->reminder_id),
                'url' => route('stores.show', $modelObject->remindable->id),
            ],
            // User Report Requests
            'generateReports' => [
                'report_name' => $modelObject->report_name,
                'file_name' => $modelObject->file_name,
                'file_path' => $modelObject->file_path,
                'disk' => $modelObject->disk ?? $this->getDefaultUploadDisk(),
                'status' => UserReportRequestStatusEnum::from($modelObject->status)->label,
                'updated_at' => DateHelper::changeDateFormat($modelObject->updated_at, 'F j, Y'),
                'is_successful' => $modelObject->status == UserReportRequestStatusEnum::Successful()->value,
                'url' => route('reports'),
            ],
        };
    }
}
