<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\Store;
use App\Models\StoreRating;
use App\Traits\HandleTransactions;
use App\Traits\ManageFilesystems;
use Carbon\Carbon;

class PhotoService
{
    use HandleTransactions;
    use ManageFilesystems;

    public function createPhoto($file, $model, $modelId, $data = [], $disk = null): mixed
    {
        $disk = $disk ?? $this->getDefaultUploadDisk();

        return $this->transact(function () use ($file, $model, $modelId, $data, $disk) {
            $photoData = $this->generatePhotoDetail($file, $model, $data, $modelId, $disk);

            $modelInstance = match ($model) {
                'store' => Store::find($modelId),
                'store-rating' => StoreRating::find($modelId),
            };

            $this->upload($file, $photoData['path'], $disk);

            return $modelInstance->photos()->create([
                'file_path' => $photoData['path'],
                'disk' => $disk,
                'description' => $photoData['description'],
            ]);
        });
    }

    public function delete(Photo $photo)
    {
        return $this->transact(function () use ($photo) {
            $this->deleteFile($photo->file_path, $photo->disk);

            $photo->delete();
        });
    }

    protected function generatePhotoDetail($file, $photoType, $data, $modelId, $disk): array
    {
        $path = $this->generateUploadBasePath();
        $baseFileName = $data['file_name'];
        
        // Always strip extension from base filename to prevent double extensions
        $baseFileName = pathinfo($baseFileName, PATHINFO_FILENAME);
        
        $date = Carbon::now()->format('Ymd_His');

        if (! isset($data['file_type'])) {
            $file_type = ($file->getExtension() === 'tmp')
                ? $file->getClientOriginalExtension()
                : $file->getExtension();
            if (! $file_type) {
                $file_type = $file->getClientOriginalExtension();
            }
        } else {
            $file_type = $data['file_type'];
        }
        $storeId = $data['store_id'] ?? '';

        switch ($photoType) {
            case 'store':
                $fileName = "{$baseFileName}-{$date}.{$file_type}";
                $path = "{$path}/store/{$modelId}/photos";
                break;
            case 'store-rating':
                $fileName = "{$baseFileName}-{$date}.{$file_type}";
                $path = "{$path}/store/{$storeId}/ratings/{$modelId}/photos";
                break;
            default:
                $fileName = "{$baseFileName}-{$date}.{$file_type}";
                $path = "{$path}";
        }

        return [
            'path' => $path.'/'.$fileName,
            'description' => $data['description'] ?? '',
        ];
    }
}
