<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\EnumDropdownController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::get('/test', [App\Http\Controllers\TestController::class, 'test']);

Route::middleware('auth')->group(function () {
    // Onboarding
    Route::get('/onboarding', [Admin\OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding/update-password', [Auth\PasswordController::class, 'onboardingUpdate'])->name('onboarding.update');
});

Route::middleware('guest')->group(function () {
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware(['auth', 'user.onboarded', 'user.active'])->group(function () {
    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/get-franchisee-region-details', [Admin\DashboardController::class, 'getFranchiseeRegionDetails'])->name('dashboard.getFranchiseeRegionDetails');
    Route::get('/dashboard/get-franchisee-count-details', [Admin\DashboardController::class, 'getFranchiseeCountDetails'])->name('dashboard.getFranchiseeCountDetails');
    Route::get('/dashboard/get-store-opening-closures', [Admin\DashboardController::class, 'getStoreOpeningClosures'])->name('dashboard.getStoreOpeningClosures');
    Route::get('/dashboard/get-store-temporary-closures', [Admin\DashboardController::class, 'getStoreTemporaryClosures'])->name('dashboard.getStoreTemporaryClosures');

    // Inbox
    Route::get('/inbox', [Admin\InboxController::class, 'index'])->name('inbox');

    // Notifications
    Route::get('/notifications/{lastIndex}/{unread}', [Admin\NotificationsController::class, 'getNotifications'])->name('notifications.getNotifications');
    Route::get('/notifications/unread-count', [Admin\NotificationsController::class, 'getUnreadCount'])->name('notifications.getUnreadCount');
    Route::post('notifications/{notification?}', [Admin\NotificationsController::class, 'markAsRead'])->name('notifications.read');

    // Franchisees
    Route::get('/franchisees', [Admin\FranchiseesController::class, 'index'])->name('franchisees');
    Route::get('/franchisees/apply/{start?}', [Admin\FranchiseesController::class, 'handleApplication'])->name('franchisees.apply');
    Route::get('/franchisees/create', [Admin\FranchiseesController::class, 'create'])->name('franchisees.create');
    Route::get('/franchisees/{franchisee}', [Admin\FranchiseesController::class, 'show'])->name('franchisees.show');
    Route::get('/franchisees/{id}/create-application', [Admin\FranchiseesController::class, 'continueApplication'])->name('franchisees.continueApplication');
    Route::get('/franchisees/{franchisee}/edit', [Admin\FranchiseesController::class, 'edit'])->name('franchisees.edit');
    Route::get('/franchisees/{franchisee}/get-details', [Admin\FranchiseesController::class, 'getQuickDetails'])->name('franchisees.getQuickDetails');
    Route::post('/franchisees/{franchisee}/update', [Admin\FranchiseesController::class, 'update'])->name('franchisees.update');
    Route::post('/franchisees/{franchisee}/cancel-application', [Admin\FranchiseesController::class, 'cancelApplication'])->name('franchisees.cancelApplication');
    Route::post('/franchises/{franchisee}/delete', [Admin\FranchiseesController::class, 'delete'])->name('franchisees.delete');
    Route::post('/franchises/{franchisee}/activate', [Admin\FranchiseesController::class, 'activate'])->name('franchisees.activate');
    Route::post('/franchises/{franchisee}/deactivate', [Admin\FranchiseesController::class, 'deactivate'])->name('franchisees.deactivate');
    Route::get('/franchisees/{franchisee}/activity-data-table', [Admin\FranchiseesController::class, 'getActivityDataTable'])->name('franchisees.activityDataTable');

    // Stores
    Route::get('/stores', [Admin\StoresController::class, 'index'])->name('stores');
    Route::get('/stores/create', [Admin\StoresController::class, 'handleCreate'])->name('stores.create');
    Route::get('/stores/{store}/edit', [Admin\StoresController::class, 'edit'])->name('stores.edit');
    Route::get('/stores/{id}/create-store', [Admin\StoresController::class, 'continue'])->name('stores.continue');
    Route::post('/stores/store', [Admin\StoresController::class, 'store'])->name('stores.store');
    Route::get('/stores/{store}', [Admin\StoresController::class, 'show'])->name('stores.show');
    Route::post('/stores/{store}/cancel-create', [Admin\StoresController::class, 'cancelCreate'])->name('stores.cancelCreate');
    Route::post('/stores/{store}/update', [Admin\StoresController::class, 'update'])->name('stores.update');
    Route::post('/stores/{store}/delete', [Admin\StoresController::class, 'delete'])->name('stores.delete');
    Route::post('/stores/{store}/activate', [Admin\StoresController::class, 'activate'])->name('stores.activate');
    Route::post('/stores/{store}/deactivate', [Admin\StoresController::class, 'deactivate'])->name('stores.deactivate');
    Route::get('/stores/{store}/get-history', [Admin\StoresController::class, 'getStoreHistory'])->name('stores.getStoreHistory');
    Route::post('/stores/{store}/add-history', [Admin\StoresController::class, 'addStoreHistory'])->name('stores.addStoreHistory');
    Route::post('/stores/{store}/add-coordinated-history', [Admin\StoresController::class, 'addCoordinatedStoreHistory'])->name('stores.addCoordinatedHistory');
    Route::get('/stores/{store}/get-notifications', [Admin\StoresController::class, 'getStoreNotification'])->name('stores.getStoreNotification');
    Route::get('/stores/{store}/{lastIndex}/get-store-ratings', [Admin\StoresController::class, 'getStoreRatings'])->name('stores.getStoreRatings');
    Route::get('/stores/{store}/activity-data-table', [Admin\StoresController::class, 'getActivityDataTable'])->name('stores.activityDataTable');

    // Store Ratings
    Route::get('/stores/store-ratings/{storeRating}', [Admin\StoreRatingsController::class, 'show'])->name('storeRatings.show');
    Route::get('/stores/store-ratings/create/{store}/{start?}', [Admin\StoreRatingsController::class, 'handleCreate'])->name('storeRatings.create');
    Route::get('/store-ratings/{id}/create', [Admin\StoreRatingsController::class, 'continue'])->name('storeRatings.continue');
    Route::get('/store-ratings/{storeRating}/upload', [Admin\StoreRatingsController::class, 'handleUpload'])->name('storeRatings.upload');
    Route::post('/store-ratings/{storeRating}/update', [Admin\StoreRatingsController::class, 'update'])->name('storeRatings.update');
    Route::post('/store-ratings/{storeRating}/delete', [Admin\StoreRatingsController::class, 'delete'])->name('storeRatings.delete');
    Route::post('/store-ratings/{id}/delete-photo', [Admin\StoreRatingsController::class, 'deletePhoto'])->name('storeRatings.deletePhoto');

    // Store Auditors
    Route::post('/store-auditors/store', [Admin\StoreAuditorsController::class, 'store'])->name('storeAuditors.store');
    Route::post('/store-auditors/{storeAuditor}/delete', [Admin\StoreAuditorsController::class, 'delete'])->name('storeAuditors.delete');

    // Documents
    Route::get('/documents', [Admin\DocumentsController::class, 'index'])->name('documents');
    Route::get('/documents/{document}/download', [Admin\DocumentsController::class, 'download'])->name('documents.download');
    Route::post('documents/upload', [Admin\DocumentsController::class, 'upload'])->name('documents.upload');
    Route::post('documents/{document}/delete', [Admin\DocumentsController::class, 'delete'])->name('documents.delete');

    // Reminders
    Route::get('/reminders/get-all/{type?}/{id?}', [Admin\RemindersController::class, 'getUpcomingReminders'])->name('reminders.getUpcomingReminders');
    Route::get('/reminders/get-today/{type?}/{id?}', [Admin\RemindersController::class, 'getTodayReminders'])->name('reminders.getTodayReminders');
    Route::get('/reminders/get-notifications/{type?}/{id?}', [Admin\RemindersController::class, 'getNotificationReminders'])->name('reminders.getNotificationReminders');
    Route::get('/reminders/today-count/{type?}/{id?}', [Admin\RemindersController::class, 'getTodayRemindersCount'])->name('reminders.getTodayRemindersCount');
    Route::post('/reminders/store', [Admin\RemindersController::class, 'store'])->name('reminders.store');
    Route::post('/reminders/{reminderInstance}/update', [Admin\RemindersController::class, 'update'])->name('reminders.update');
    Route::post('/reminders/{reminderInstance}/delete', [Admin\RemindersController::class, 'delete'])->name('reminders.delete');
    Route::post('/reminders/{reminderInstance}/toggle-status', [Admin\RemindersController::class, 'toggleStatus'])->name('reminders.toggleStatus');
    Route::post('/reminders/{reminderInstance}/update-notification-duration', [Admin\RemindersController::class, 'updateRemindersDuration'])->name('reminders.updateNotificationDuration');

    // Teams
    Route::get('/teams', [Admin\TeamsController::class, 'index'])->name('teams');
    Route::get('/teams/{team}/show', [Admin\TeamsController::class, 'show'])->name('teams.show');
    Route::get('/teams/{team}/edit', [Admin\TeamsController::class, 'edit'])->name('teams.edit');
    Route::post('/teams/invite', [Admin\TeamsController::class, 'invite'])->name('teams.invite');
    Route::post('/teams/{team}/update', [Admin\TeamsController::class, 'update'])->name('teams.update');
    Route::post('/teams/{team}/reset-password', [Admin\TeamsController::class, 'resetPassword'])->name('teams.resetPassword');
    Route::post('/teams/{team}/delete', [Admin\TeamsController::class, 'delete'])->name('teams.delete');
    Route::post('/teams/{team}/activate', [Admin\TeamsController::class, 'activate'])->name('teams.activate');
    Route::post('/teams/{team}/deactivate', [Admin\TeamsController::class, 'deactivate'])->name('teams.deactivate');

    // User Roles and Permissions
    Route::post('user-roles', [Admin\UserRolesController::class, 'store'])->name('userRoles.store');
    Route::post('user-roles/{id}/update', [Admin\UserRolesController::class, 'update'])->name('userRoles.update');
    Route::post('user-roles/{id}/delete', [Admin\UserRolesController::class, 'delete'])->name('userRoles.delete');
    Route::get('user-roles/get-permission-list/{id}', [Admin\UserRolesController::class, 'getPermissionList'])->name('userRoles.getPermissionList');

    Route::post('role-permissions/{id}/update', [Admin\RolePermissionsController::class, 'update'])->name('rolePermissions.update');

    // Reports
    Route::get('/reports', [Admin\ReportsController::class, 'index'])->name('reports');
    Route::post('/reports/generate', [Admin\ReportsController::class, 'generate'])->name('reports.generate');

    // Royalty
    Route::get('/royalty', [Admin\RoyaltyController::class, 'index'])->name('royalty');
    Route::post('/royalty/generate', [Admin\RoyaltyController::class, 'generate'])->name('royalty.generate');
    Route::post('/royalty/generate-history', [Admin\RoyaltyController::class, 'generateSalesHistory'])->name('royalty.generate.sales_history');
    Route::get('/royalty/generating', [Admin\RoyaltyController::class, 'getGeneratingRoyaltyBatches']);
    Route::get('/royalty/download/output/{id}', [Admin\RoyaltyController::class, 'downloadOutput'])->name('royalty.download.output');
    Route::get('/royalty/download/upload/{id}', [Admin\RoyaltyController::class, 'downloadUpload'])->name('royalty.download.upload');
    Route::post('/royalty/invalidate/{id}', [Admin\RoyaltyController::class, 'invalidate'])->name('royalty.invalidate');

    // Settings
    Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/import', [Admin\SettingsController::class, 'import'])->name('settings.import');
    Route::post('/settings/update-profile', [Admin\SettingsController::class, 'updateProfile'])->name('settings.updateProfile');
    Route::post('/settings/update-password', [Admin\SettingsController::class, 'updatePassword'])->name('settings.updatePassword');

    // Document Import
    Route::post('/settings/import/sync', [Admin\DocumentImportController::class, 'sync'])->name('admin.settings.import.sync');

    // Settings > Notifications
    Route::post('/settings/toggle-notification/{reminderId}', [Admin\RemindersController::class, 'toggleSettingsReminders'])->name('settings.toggleNotification');
    Route::post('/settings/update-notification-duration/{reminderId}', [Admin\RemindersController::class, 'updateSettingsRemindersDuration'])->name('settings.updateNotificationDuration');

    // Activities
    Route::get('/activities', [Admin\ActivitiesController::class, 'index'])->name('activities');

    // Store Rating - Questionnaires
    Route::post('store-rating-questionnaire/{storeRatingQuestionnaire}/update', [Admin\StoreRatingQuestionnairesController::class, 'update'])->name('storeRatingQuestionnaire.update');

    // Remarks
    Route::post('/{modelId}/{model}/remarks', [Admin\RemarksController::class, 'store'])->name('remarks.store');
    Route::post('/remarks/{remark}/update', [Admin\RemarksController::class, 'update'])->name('remarks.update');
    Route::post('/remarks/{remark}/delete', [Admin\RemarksController::class, 'delete'])->name('remarks.delete');

    // DataTables
    Route::get('retrieve-datatables/franchisees', [Admin\FranchiseesController::class, 'getDataTable'])->name('franchisees.dataTable');
    Route::get('retrieve-datatables/stores', [Admin\StoresController::class, 'getDataTable'])->name('stores.dataTable');
    Route::get('retrieve-datatables/store-auditors', [Admin\StoreAuditorsController::class, 'getDataTable'])->name('storeAuditors.dataTable');
    Route::get('retrieve-datatables/teams', [Admin\TeamsController::class, 'getDataTable'])->name('teams.dataTable');
    Route::get('retrieve-datatables/documents/{model?}/{id?}', [Admin\DocumentsController::class, 'getDataTable'])->name('documents.dataTable');
    Route::get('retrieve-datatables/activities', [Admin\ActivitiesController::class, 'getDataTable'])->name('activities.dataTable');
    Route::get('retrieve-datatables/royalty', [Admin\RoyaltyController::class, 'getDataTable'])->name('royalty.dataTable');

    // DataList - dropdowns
    Route::get('get-data-list/dropdowns-enums/{key}', [EnumDropdownController::class, 'getDataList'])->name('enums.getDataList');
    Route::get('get-data-list/franchisees', [Admin\FranchiseesController::class, 'getDataList'])->name('franchisees.getDataList');
    Route::get('get-data-list/stores', [Admin\StoresController::class, 'getDataList'])->name('stores.getDataList');
    Route::get('get-data-list/user-roles', [Admin\UserRolesController::class, 'getDataList'])->name('userRoles.getDataList');
    Route::get('get-data-list/store-auditors/{id}', [Admin\StoreAuditorsController::class, 'getDataList'])->name('storeAuditors.getDataList');
    Route::get('get-data-list/activities', [Admin\ActivitiesController::class, 'getDataList'])->name('activities.getDataList');
});

require __DIR__.'/auth.php';
