<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Franchisee;
use App\Models\Store;
use App\Models\User;
use App\Traits\HandleTransactions;
use App\Traits\ManageFilesystems;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

class DocumentImportService
{
    use HandleTransactions;
    use ManageFilesystems;

    public function import(string $filePath, User $user): array
    {
        $sourceStorageDisk = 'client_local'; // Source disk where files are read from
        $destinationStorageDisk = $this->getDefaultUploadDisk(); // Destination disk where files are stored

        // Verify source directory exists
        $sourceRoot = config('filesystems.disks.'.$sourceStorageDisk.'.root');
        if (! is_dir($sourceRoot)) {
            Log::error('Document import: Source directory does not exist', [
                'disk' => $sourceStorageDisk,
                'root_path' => $sourceRoot,
            ]);
            throw new \Exception("Source directory for disk '{$sourceStorageDisk}' does not exist at '{$sourceRoot}'. Please create the directory or verify the disk configuration.");
        }

        // Handle S3 private files by downloading to temporary location
        $tempFilePath = null;
        $actualFilePath = $filePath;

        // Check if file is stored on the configured disk (not a local file path)
        if (! file_exists($filePath) && Storage::disk($sourceStorageDisk)->exists($filePath)) {
            // Create temporary file
            $tempFileName = 'temp_import_'.Str::random(10).'.xlsx';
            $tempFilePath = storage_path('app/temp/'.$tempFileName);

            // Ensure temp directory exists
            if (! is_dir(dirname($tempFilePath))) {
                mkdir(dirname($tempFilePath), 0755, true);
            }

            // Download file from S3 to temporary location
            $fileContents = $this->readFile($filePath);
            file_put_contents($tempFilePath, $fileContents);
            $actualFilePath = $tempFilePath;
        }

        try {
            $data = Excel::toArray(new class implements ToArray
            {
                public function array(array $array): array
                {
                    return $array;
                }
            }, $actualFilePath);

            $rows = $data[0] ?? [];
            $headers = array_shift($rows); // Remove header row

            // Validate headers
            if (count($headers) < 7 ||
                $headers[0] !== 'DocID' ||
                $headers[1] !== 'OwnerCode' ||
                $headers[4] !== 'FileName' ||
                $headers[5] !== 'FilePath') {
                throw new \Exception('Invalid Excel headers. Expected: DocID, OwnerCode, UploadDate, Description, FileName, FilePath, CreatedDate');
            }

            $successCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and Excel is 1-indexed
                $ownerCode = $row[1] !== null ? trim((string) $row[1]) : null;

                if ($ownerCode == null || $ownerCode == '') {
                    $skippedCount++;
                    Log::info('Document import: Skipping row with empty owner code', ['row' => $rowNumber]);

                    continue;
                }

                // Process each row in its own transaction to prevent rollback of successful rows
                try {
                    $this->transact(function () use ($row, $user, &$successCount, &$errors, $sourceStorageDisk, $destinationStorageDisk) {
                        $docId = $row[0] ?? null;
                        $ownerCode = $row[1] !== null ? trim((string) $row[1]) : null;
                        $fileName = $row[4] ?? null;
                        $filePath = $row[5] ?? '';

                        // Normalize path for Storage facade
                        $normalizedPath = str_replace(['\\', '/'], '/', trim($filePath));
                        $normalizedPath = trim($normalizedPath, '/');
                        $originalFilePath = $normalizedPath.'/'.$fileName;

                        // Validate required fields
                        if (empty($docId) || empty($ownerCode) || empty($fileName) || empty($originalFilePath)) {
                            throw new \Exception('Missing required fields (DocID, OwnerCode, FileName, or FilePath)');
                        }

                        // Detect if it's a franchisee (starts with 'F') or store
                        $isFranchisee = str_starts_with(strtoupper($ownerCode), 'F');

                        // Look up the appropriate model
                        if ($isFranchisee) {
                            $owner = Franchisee::where('franchisee_code', $ownerCode)->first();
                            $ownerType = 'franchisee';
                        } else {
                            $owner = Store::where('sales_point_code', $ownerCode)->first();
                            $ownerType = 'store';
                        }

                        if (! $owner) {
                            Log::warning('Document import: Owner not found, skipping row', [
                                'owner_code' => $ownerCode,
                                'owner_type' => $ownerType,
                                'row' => $docId,
                                'file_name' => $fileName,
                            ]);
                            throw new \Exception(ucfirst($ownerType)." with code '{$ownerCode}' not found");
                        }

                        // Check if file exists at the original path
                        if (! Storage::disk($sourceStorageDisk)->exists($originalFilePath)) {
                            Log::warning('Document import: Source file not found, skipping row', [
                                'owner_code' => $ownerCode,
                                'owner_type' => $ownerType,
                                'doc_id' => $docId,
                                'file_name' => $fileName,
                                'file_path' => $originalFilePath,
                            ]);
                            throw new \Exception("File not found at path '{$originalFilePath}' - skipping this document");
                        }

                        // Generate new file path
                        $basePath = $this->generateUploadBasePath();
                        $newFilePath = "{$basePath}/{$ownerType}/{$owner->id}/documents/{$fileName}";

                        // Check if document with same external_id already exists
                        $existingDocument = Document::where('external_id', $docId)
                            ->where('documentable_type', $ownerType)
                            ->where('documentable_id', $owner->id)
                            ->first();

                        if ($existingDocument) {
                            // Update existing document (overwrite)
                            $this->moveFile($originalFilePath, $newFilePath, $sourceStorageDisk, $destinationStorageDisk);

                            $existingDocument->update([
                                'document_name' => $fileName,
                                'file_path' => $newFilePath,
                                'disk' => $destinationStorageDisk,
                                'file_type' => $this->getFileType($originalFilePath),
                                'file_size' => Storage::disk($sourceStorageDisk)->size($originalFilePath),
                                'updated_by_id' => $user->id,
                            ]);
                        } else {
                            // Create new document
                            $this->moveFile($originalFilePath, $newFilePath, $sourceStorageDisk, $destinationStorageDisk);

                            Document::create([
                                'external_id' => $docId,
                                'documentable_type' => $ownerType,
                                'documentable_id' => $owner->id,
                                'document_name' => $fileName,
                                'file_path' => $newFilePath,
                                'disk' => $destinationStorageDisk,
                                'file_type' => $this->getFileType($originalFilePath),
                                'file_size' => Storage::disk($sourceStorageDisk)->size($originalFilePath),
                                'created_by_id' => $user->id,
                                'updated_by_id' => $user->id,
                            ]);
                        }

                        $successCount++;
                    });
                } catch (\Exception $e) {
                    // Check if this is a skippable error (missing file or store)
                    $errorMessage = $e->getMessage();
                    if (strpos($errorMessage, 'not found') !== false || strpos($errorMessage, 'skipping') !== false) {
                        $skippedCount++;
                        Log::info('Document import: Skipped row', [
                            'row' => $rowNumber,
                            'reason' => $errorMessage,
                            'data' => $row,
                        ]);
                    } else {
                        $errors[] = "Row {$rowNumber}: ".$errorMessage;
                        Log::error('Document import row error', [
                            'row' => $rowNumber,
                            'error' => $errorMessage,
                            'data' => $row,
                        ]);
                    }
                }
            }

            return [
                'success' => $successCount,
                'skipped' => $skippedCount,
                'errors' => $errors,
            ];
        } finally {
            // Clean up temporary file if it was created
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    private function moveFile(string $sourcePath, string $destinationPath, string $sourceStorageDisk, string $destinationStorageDisk): void
    {
        try {
            // Copy file to new location (not deleting original from source disk)
            $fileContents = Storage::disk($sourceStorageDisk)->get($sourcePath);

            if ($fileContents === null) {
                throw new \Exception("Unable to read file contents from '{$sourcePath}' on disk '{$sourceStorageDisk}'. File may be empty, corrupted, or inaccessible - skipping this document");
            }

            Storage::disk($destinationStorageDisk)->put($destinationPath, $fileContents);
        } catch (\Exception $e) {
            Log::warning('Document import: Failed to move file', [
                'source_path' => $sourcePath,
                'destination_path' => $destinationPath,
                'source_disk' => $sourceStorageDisk,
                'destination_disk' => $destinationStorageDisk,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getFileType(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Map common extensions to MIME types
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}
