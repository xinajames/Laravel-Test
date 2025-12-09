<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DocumentImportJob;
use App\Traits\ManageFilesystems;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DocumentImportController extends Controller
{
    use ManageFilesystems;

    public function sync(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $fileName = 'document-import-'.time().'.'.$file->getClientOriginalExtension();
            $basePath = $this->generateUploadBasePath();
            $filePath = "{$basePath}/imports/{$fileName}";

            // Upload the Excel file
            $uploadSuccess = $this->upload($file, $filePath);

            if (! $uploadSuccess) {
                throw ValidationException::withMessages([
                    'file' => 'Failed to upload the file. Please try again.',
                ]);
            }

            // Dispatch the import job
            DocumentImportJob::dispatch($filePath, $request->user()->id);

            return redirect()->back()->with('success', 'Document import has been queued for processing. You will receive a notification when it completes.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }
}
