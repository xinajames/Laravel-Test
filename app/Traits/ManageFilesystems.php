<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait ManageFilesystems
{
    public function generateUploadBasePath(): string
    {
        return '/jform-'.config('app.env').'-filesystem';
    }

    /**
     * Get the default upload disk from configuration
     */
    public function getDefaultUploadDisk(): string
    {
        return config('filesystems.upload_disk', 'local');
    }

    public function upload($file, $path, $disk = null): bool
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        try {
            switch ($disk) {
                case 's3':
                    Storage::disk('s3')->put($path, file_get_contents($file));
                    break;
                case 'local':
                    Storage::disk('local')->put($path, file_get_contents($file));
                    break;
                default:
                    Log::error("Unsupported disk type: {$disk}");

                    return false;
            }

            return true;
        } catch (Exception $exception) {
            Log::error('Failed to upload to '.$disk.'filesystem - '.$path);
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());

            return false;
        }
    }

    public function uploadData($data, $path, $disk = null): bool
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        try {
            switch ($disk) {
                case 's3':
                    Storage::disk('s3')->put($path, $data);
                    break;
                case 'local':
                    Storage::disk('local')->put($path, $data);
                    break;
                default:
                    Log::error("Unsupported disk type: {$disk}");

                    return false;
            }

            return true;
        } catch (Exception $exception) {
            Log::error('Failed to upload data to '.$disk.'filesystem - '.$path);
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());

            return false;
        }
    }

    public function retrieveFile($path, $disk = null): string
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        try {
            $storageDisk = Storage::disk($disk);

            // Both S3 and local disks support temporaryUrl in Laravel when properly configured
            return $storageDisk->temporaryUrl(ltrim($path, '/'), now()->addHour());
        } catch (Exception $exception) {
            Log::error("Failed to retrieve temporary URL for {$disk} disk - {$path}");
            Log::error($exception->getMessage());
            // Fallback to regular URL method
            try {
                return Storage::disk($disk)->url($path);
            } catch (Exception $fallbackException) {
                Log::error('Failed to retrieve fallback URL: '.$fallbackException->getMessage());

                return '';
            }
        }
    }

    public function deleteFile($path, $disk = null): bool
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        return Storage::disk($disk)->delete($path);
    }

    public function readFile($path, $disk = null): null | string
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();
        return Storage::disk($disk)->get($path);
    }

    public function retrieveFileUrl($path, $disk = null): string
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        return Storage::disk($disk)->url($path);
    }

    /**
     * Check if a file exists on the specified disk
     */
    public function fileExists($path, $disk = null): bool
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file size from the specified disk
     */
    public function getFileSize($path, $disk = null): int
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        return Storage::disk($disk)->size($path);
    }

    /**
     * Download a file from the specified disk
     */
    public function downloadFile($path, $filename, $disk = null)
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        try {
            $storageDisk = Storage::disk($disk);

            if (!$storageDisk->exists($path)) {
                abort(404, 'File not found.');
            }

            return $storageDisk->download($path, $filename);
        } catch (Exception $exception) {
            Log::error("Failed to download file from {$disk} disk - {$path}");
            Log::error($exception->getMessage());
            abort(500, 'Failed to download file.');
        }
    }
}
