<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreReminderInstanceRequest;
use App\Http\Requests\UpdateReminderInstanceRequest;
use App\Models\ReminderInstance;
use App\Services\ReminderInstanceService;
use App\Services\ReminderService;
use App\Traits\HasUserPermissions;
use Illuminate\Http\Request;

class RemindersController
{
    use HasUserPermissions;

    public function __construct(
        private ReminderInstanceService $reminderInstanceService,
        private ReminderService $reminderService
    ) {}

    public function toggleSettingsReminders(Request $request, $reminderId)
    {
        $this->checkUserPermission('settings-notifications');

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $this->reminderService->toggleReminderStatus($reminderId, $request->boolean('enabled'));

        return redirect()->back()->with('success', __('alert.settings.reminder.toggled.success'));
    }

    public function updateSettingsRemindersDuration(Request $request, $reminderId)
    {
        $this->checkUserPermission('settings-notifications');

        $validated = $request->validate([
            'value' => 'required|integer|min:1',
            'unit' => 'required|string|in:days,weeks,months,years',
        ]);

        $this->reminderService->updateDaysBefore(
            $reminderId,
            $validated['value'],
            $validated['unit']
        );

        return redirect()->back()->with('success', __('alert.settings.reminder.updateDuration.success'));
    }

    public function getUpcomingReminders($type = null, $id = null)
    {
        $reminders = $this->reminderInstanceService->getUpcomingReminders($type, $id);

        return response()->json($reminders);
    }

    public function getTodayReminders($type = null, $id = null)
    {
        return $this->reminderInstanceService->getTodayReminders($type, $id);
    }

    public function getNotificationReminders($type = null, $id = null)
    {
        return $this->reminderInstanceService->getNotificationReminders($type, $id);
    }

    public function store(StoreReminderInstanceRequest $request)
    {
        $this->checkUserPermission('stores-notifications-reminders');

        $input = $request->all();

        $this->reminderInstanceService->store($input);

        return redirect()->back()->with('success', __('alert.reminder.store.success'));
    }

    public function update(UpdateReminderInstanceRequest $request, ReminderInstance $reminderInstance)
    {
        $this->checkUserPermission('stores-notifications-reminders');

        $input = $request->all();

        $this->reminderInstanceService->update($reminderInstance, $input);

        return redirect()->back()->with('success', __('alert.reminder.update.success'));
    }

    public function delete(ReminderInstance $reminderInstance)
    {
        $this->checkUserPermission('stores-notifications-reminders');

        $this->reminderInstanceService->delete($reminderInstance);

        return redirect()->back()->with('success', __('alert.reminder.delete.success'));
    }

    public function toggleStatus(Request $request, $reminderInstanceId)
    {
        $this->checkUserPermission('stores-notifications-reminders');

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $this->reminderInstanceService->toggleStatus($reminderInstanceId, $request->boolean('enabled'));

        return redirect()->back()->with('success', __('alert.reminder.toggled.success'));
    }

    public function updateRemindersDuration(Request $request, $reminderInstanceId)
    {
        $this->checkUserPermission('stores-notifications-reminders');

        $validated = $request->validate([
            'value' => 'required|integer|min:1',
            'unit' => 'required|string|in:days,weeks,months,years',
        ]);

        $this->reminderInstanceService->updateDaysBefore(
            $reminderInstanceId,
            $validated['value'],
            $validated['unit']
        );

        return redirect()->back()->with('success', __('alert.reminder.updateDuration.success'));
    }

    public function getTodayRemindersCount($type = null, $id = null)
    {
        return $this->reminderInstanceService->getTodayRemindersCount($type, $id);
    }
}
