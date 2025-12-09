<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DataImportNotification;
use App\Services\Data\Migration\JFMDataMigrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DataImportJob implements ShouldQueue
{
    use Queueable;

    private string $filePath;

    private int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->queue = 'default';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            Log::error('Data import job failed: User not found', ['user_id' => $this->userId]);

            return;
        }

        try {
            $service = app(JFMDataMigrationService::class);
            $result = $service->importData($this->filePath);

            $franchiseeCount = count($result['franchisees'] ?? []);
            $storeCount = count($result['stores'] ?? []);
            $errors = $result['errors'] ?? [];

            // Send notification (success or with errors)
            $user->notify(new DataImportNotification($franchiseeCount + $storeCount, $errors));

            Log::info('Data import completed', [
                'user_id' => $this->userId,
                'franchisees' => $franchiseeCount,
                'stores' => $storeCount,
                'errors' => count($errors),
            ]);
        } catch (Throwable $exception) {
            Log::error('Data import job failed', [
                'user_id' => $this->userId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Send failure notification
            $user->notify(new DataImportNotification(0, [$exception->getMessage()]));

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new DataImportNotification(0, ['Job failed: '.$exception->getMessage()]));
        }
    }
}
