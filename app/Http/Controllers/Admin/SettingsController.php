<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPassword;
use App\Http\Requests\UpdateUserProfile;
use App\Jobs\DataImportJob;
use App\Jobs\JbmisHistoryImportJob;
use App\Models\User;
use App\Services\Data\Migration\JFMDataMigrationService;
use App\Services\ReminderService;
use App\Services\UserService;
use App\Traits\HasUserPermissions;
use App\Traits\ManageFilesystems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SettingsController extends Controller
{
    use HasUserPermissions;
    use ManageFilesystems;

    public function __construct(
        private UserService $userService,
        private ReminderService $reminderService,
        private JFMDataMigrationService $jFMDataMigrationService,
    ) {}

    public function index()
    {
        $user = User::find(auth()->user()->id);
        $notificationSettings = $this->reminderService->fetchReminders();

        return Inertia::render('Admin/Settings/Index', [
            'user' => $user,
            'notificationSettings' => $notificationSettings,
        ]);
    }

    public function updateProfile(UpdateUserProfile $request, User $profile)
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Handle profile photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile_photos', 'public');
            $validated['avatar'] = $path;
        }

        // Update user
        $this->userService->update($validated, $user);

        return redirect()->back()->with('success', __('alert.user.update.success'));
    }

    public function updatePassword(UpdateUserPassword $request, User $profile)
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Update password
        $this->userService->updatePassword($validated, $user);

        return redirect()->back()->with('success', __('alert.user.update.success'));
    }

    /**
     * Import JFM data from Excel file
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $request->validate([
            'franchisee_branch_master_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            'import_type' => 'required|in:store_data,jbmis_history',
        ]);

        try {
            $file = $request->file('franchisee_branch_master_file');
            $fileName = 'data-import-'.time().'.'.$file->getClientOriginalExtension();
            $basePath = $this->generateUploadBasePath();
            $filePath = "{$basePath}/imports/{$fileName}";

            // Upload the Excel file
            $uploadSuccess = $this->upload($file, $filePath);

            if (! $uploadSuccess) {
                throw ValidationException::withMessages([
                    'franchisee_branch_master_file' => 'Failed to upload the file. Please try again.',
                ]);
            }

            // Dispatch the appropriate import job based on import type
            if ($request->input('import_type') === 'jbmis_history') {
                JbmisHistoryImportJob::dispatch($filePath, $request->user()->id);
                $message = 'JBMIS history import has been queued for processing. You will receive a notification when it completes.';
            } else {
                // Default to store data import (maintains 100% existing functionality)
                DataImportJob::dispatch($filePath, $request->user()->id);
                $message = 'Data import has been queued for processing. You will receive a notification when it completes.';
            }

            return response()->json([
                'message' => $message,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Data import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'message' => 'Import failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
