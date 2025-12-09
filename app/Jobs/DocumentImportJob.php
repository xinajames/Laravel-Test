<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DocumentImportNotification;
use App\Services\DocumentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DocumentImportJob implements ShouldQueue
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
            Log::error('Document import job failed: User not found', ['user_id' => $this->userId]);

            return;
        }

        try {
            $service = app(DocumentImportService::class);
            $result = $service->import($this->filePath, $user);

            // Send success notification
            $user->notify(new DocumentImportNotification($result['success'], $result['errors']));

            Log::info('Document import completed', [
                'user_id' => $this->userId,
                'processed' => $result['success'],
                'skipped' => $result['skipped'] ?? 0,
                'errors' => count($result['errors']),
            ]);
        } catch (Throwable $exception) {
            Log::error('Document import job failed', [
                'user_id' => $this->userId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Send failure notification
            $user->notify(new DocumentImportNotification(0, [$exception->getMessage()]));

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new DocumentImportNotification(0, ['Job failed: '.$exception->getMessage()]));
        }
    }
}
