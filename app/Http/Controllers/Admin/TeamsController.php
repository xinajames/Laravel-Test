<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\User;
use App\Services\UserService;
use App\Traits\HasUserPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamsController extends Controller
{
    use HasUserPermissions;

    public function __construct(
        private UserService $userService
    ) {}

    public function index()
    {
        $this->checkUserPermission('team');

        return Inertia::render('Admin/Teams/Index');
    }

    public function show(User $team)
    {
        $this->checkUserPermission('team');

        $team->status_label = UserStatusEnum::getDescription($team->status);

        return Inertia::render('Admin/Teams/Show', [
            'team' => $team,
        ]);
    }

    public function edit(User $team)
    {
        $this->checkUserPermission('team', false);

        if (auth()->id() === $team->id) {
            return redirect()->route('teams')->with('error', __('You cannot edit your own account.'));
        }

        $team->status_label = UserStatusEnum::getDescription($team->status);

        return Inertia::render('Admin/Teams/Edit', [
            'team' => $team,
        ]);
    }

    public function invite(Request $request)
    {
        $this->checkUserPermission('team', false);

        $input = $request->all();

        $this->userService->storeAdminUser($input, 'pass');

        return redirect()->back()->with('success', __('alert.user.invite.success'));
    }

    public function resetPassword(ResetPasswordRequest $request, User $team)
    {
        $this->checkUserPermission('team', false);

        $this->userService->resetPassword($team, $request->input('new_password'));

        return redirect()->back()->with('success', __('alert.user.update.success'));
    }

    public function update(UpdateTeamRequest $request, User $team)
    {
        $this->checkUserPermission('team', false);

        $validated = $request->validated();

        $this->userService->update($validated, $team);

        return redirect(route('teams.show', $team))->with('success', __('alert.user.update.success'));
    }

    public function delete(User $team)
    {
        $this->checkUserPermission('team', false);

        if (auth()->id() === $team->id) {
            return redirect()->route('teams')->with('error', __('You cannot delete your own account.'));
        }

        $this->userService->delete($team);

        return redirect()->route('teams')->with('success', __('alert.user.delete.success', ['user' => $team->name]));
    }

    public function activate(User $team)
    {
        $this->checkUserPermission('team', false);

        $this->userService->updateIsActive($team, 'users.activate');

        return redirect()->back()->with('success', __('alert.user.activate.success', ['user' => $team->name]));
    }

    public function deactivate(User $team)
    {
        $this->checkUserPermission('team', false);

        if (auth()->id() === $team->id) {
            return redirect()->route('teams')->with('error', __('You cannot deactivate your own account.'));
        }

        $this->userService->updateIsActive($team, 'users.deactivate', false);

        return redirect()->back()->with('success', __('alert.user.deactivate.success', ['user' => $team->name]));
    }

    public function getDataTable(): JsonResponse
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int) request('perPage', 10);

        $data = $this->userService->getAdminsDataTable($filters, $orders, $perPage);

        return response()->json($data);
    }
}
