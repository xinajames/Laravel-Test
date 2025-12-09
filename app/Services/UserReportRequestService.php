<?php

namespace App\Services;

use App\Enums\ReportOptionEnum;
use App\Enums\UserReportRequestStatusEnum;
use App\Exports\Reports\BarangayJbsReportExport;
use App\Exports\Reports\ContractOfLeaseReportExport;
use App\Exports\Reports\FranchiseeReportExport;
use App\Exports\Reports\GanvioReportExport;
use App\Exports\Reports\InsuranceReportExport;
use App\Exports\Reports\RenewalsReportExport;
use App\Exports\Reports\SalesPerformanceReportExport;
use App\Helpers\DateHelper;
use App\Models\UserReportRequest;
use App\Notifications\GenerateReportNotification;
use App\Traits\HandleTransactions;
use App\Traits\ManageFilesystems;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

class UserReportRequestService
{
    use HandleTransactions;
    use ManageFilesystems;

    public function requestReport(array $data, $user = null)
    {
        return $this->transact(function () use ($data) {
            $userReportRequest = UserReportRequest::create($data);

            return $userReportRequest;
        });
    }

    public function getReportOptions(): array
    {
        $currentUser = auth()->id();
        $options = [];

        foreach (ReportOptionEnum::cases() as $case) {
            $reportType = $case->value;

            // Latest request (any status)
            $latestRequest = UserReportRequest::where('user_id', $currentUser)
                ->where('report_type', $reportType)
                ->latest('updated_at')
                ->first();

            // Latest successful request (for file download)
            $latestReport = UserReportRequest::where('user_id', $currentUser)
                ->where('report_type', $reportType)
                ->where('status', UserReportRequestStatusEnum::Successful()->value)
                ->latest('updated_at')
                ->first();

            $filePath = $latestReport?->file_path;
            $disk = $latestReport?->disk ?? $this->getDefaultUploadDisk();
            $fileUrl = $filePath
                ? $this->retrieveFile($filePath, $disk)
                : null;

            // Check if the user has ongoing or pending - restrict generate on frontend
            $hasPendingOrOngoing = UserReportRequest::where('user_id', $currentUser)
                ->where('report_type', $reportType)
                ->whereIn('status', [
                    UserReportRequestStatusEnum::Pending()->value,
                    UserReportRequestStatusEnum::Ongoing()->value,
                ])
                ->exists();

            $options[] = [
                'label' => ReportOptionEnum::getDescription($reportType),
                'value' => $reportType,
                'file_name' => $latestReport?->file_name,
                'file_url' => $fileUrl,
                'created_at' => $latestReport
                    ? DateHelper::changeDateTimeFormat($latestReport->created_at)
                    : null,
                'last_status' => $latestRequest?->status,
                'allow_generate' => ! $hasPendingOrOngoing,
            ];
        }

        return $options;
    }

    public function generateReport(UserReportRequest $userReportRequest)
    {
        $this->transact(function () use ($userReportRequest) {
            $filterData = json_decode($userReportRequest->filter_data, true);
            $disk = $this->getDefaultUploadDisk();
            $reportType = (int) $userReportRequest->report_type;

            switch ($reportType) {
                case ReportOptionEnum::GanvioReport()->value:
                    $generatedFile = $this->generateFilePath(
                        $userReportRequest->user_id,
                        $userReportRequest->report_name,
                        'xlsx'
                    );

                    Excel::store(new GanvioReportExport($filterData), $generatedFile['path'], $disk);
                    break;

                case ReportOptionEnum::BarangayWithJbs()->value:
                    $generatedFile = $this->generateFilePath(
                        $userReportRequest->user_id,
                        $userReportRequest->report_name,
                        'xlsx'
                    );

                    Excel::store(new BarangayJbsReportExport($filterData), $generatedFile['path'], $disk);
                    break;

                case ReportOptionEnum::Renewals()->value:
                    $appendFilteredDate = $this->getAppendFilterDate($filterData['year']);
                    $generatedFile = $this->generateFilePath(
                        $userReportRequest->user_id,
                        $userReportRequest->report_name,
                        'xlsx',
                        $appendFilteredDate
                    );

                    Excel::store(new RenewalsReportExport($filterData), $generatedFile['path'], $disk);
                    break;

                case ReportOptionEnum::JBSSalesPerformance()->value:
                    $appendFilteredDate = $this->getAppendFilterDate($filterData['year']);
                    $generatedFile = $this->generateFilePath(
                        $userReportRequest->user_id,
                        $userReportRequest->report_name,
                        'xlsx',
                        $appendFilteredDate
                    );

                    Excel::store(new SalesPerformanceReportExport($filterData), $generatedFile['path'], $disk);
                    break;

                case ReportOptionEnum::FranchiseeReport()->value:
                    $generatedFile = $this->generateFilePath(
                        $userReportRequest->user_id,
                        $userReportRequest->report_name,
                        'xlsx'
                    );

                    Excel::store(new FranchiseeReportExport($filterData), $generatedFile['path'], $disk);
                    break;

                case ReportOptionEnum::InsuranceReport()->value:
                    $generatedFile = $this->generateFilePath(
                        $userReportRequest->user_id,
                        $userReportRequest->report_name,
                        'xlsx'
                    );

                    Excel::store(new InsuranceReportExport($filterData), $generatedFile['path'], $disk);
                    break;

                case ReportOptionEnum::ContractOfLeaseReport()->value:
                    $generatedFile = $this->generateFilePath(
                        $userReportRequest->user_id,
                        $userReportRequest->report_name,
                        'xlsx'
                    );

                    Excel::store(new ContractOfLeaseReportExport($filterData), $generatedFile['path'], $disk);
                    break;

                default:
                    $userReportRequest->status = UserReportRequestStatusEnum::Failed()->value;
                    $userReportRequest->save();

                    return;
            }

            $filePath = $generatedFile['path'] ?? null;
            $fileName = $generatedFile['name'] ?? null;

            if ($filePath && Storage::disk($disk)->exists($filePath)) {
                $userReportRequest->file_name = $fileName;
                $userReportRequest->file_path = $filePath;
                $userReportRequest->disk = $disk;
                $userReportRequest->status = UserReportRequestStatusEnum::Successful()->value;
            } else {
                $userReportRequest->status = UserReportRequestStatusEnum::Failed()->value;
            }

            $userReportRequest->save();

            $notification = new GenerateReportNotification($userReportRequest);
            NotificationService::handleNotification($userReportRequest->user, $notification);
        });
    }

    private function generateFilePath($id, $fileName, $fileType, $appendFilteredDate = null): array
    {
        $path = '/jform-'.config('app.env').'-filesystem';
        $date = Carbon::now();
        $dateString = $date->format('ymd_His');

        $fileName = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($fileName));
        $fileName = preg_replace('/_+/', '_', $fileName);
        $fileName = trim($fileName, '_');

        $name = $appendFilteredDate
            ? "{$fileName}_{$id}_{$appendFilteredDate}_{$dateString}.{$fileType}"
            : "{$fileName}_{$id}_{$dateString}.{$fileType}";

        return [
            'name' => $name,
            'path' => "{$path}/generated-reports/$name",
        ];
    }

    private function getAppendFilterDate($dateFrom, $dateTill = null): string
    {
        try {
            $carbonFrom = strlen($dateFrom) === 4
                ? Carbon::createFromFormat('Y', $dateFrom)
                : Carbon::parse($dateFrom);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid dateFrom format');
        }

        $formattedFrom = $carbonFrom->format(strlen($dateFrom) === 4 ? 'Y' : 'Y_m_d');

        if (! empty($dateTill)) {
            try {
                $carbonTo = Carbon::parse($dateTill);
                $formattedTo = $carbonTo->format('Y_m_d');

                return "{$formattedFrom}_t{$formattedTo}";
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }

        return $formattedFrom;
    }
}
