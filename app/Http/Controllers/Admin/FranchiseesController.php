<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FranchiseeStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFranchiseeRequest;
use App\Models\Franchisee;
use App\Models\User;
use App\Services\FranchiseeService;
use App\Traits\HasUserPermissions;
use App\Traits\ManageActivities;
use App\Traits\ManageFilesystems;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FranchiseesController extends Controller
{
    use HasUserPermissions;
    use ManageActivities;
    use ManageFilesystems;

    public function __construct(
        private FranchiseeService $franchiseeService
    ) {}

    public function index(): InertiaResponse
    {
        $this->checkUserPermission('franchisees');

        $user = auth()->user();
        $hasPendingApplication = $user->pendingFranchiseeApplication;

        return Inertia::render('Admin/Franchisees/Index', [
            'pendingApplication' => $user->pendingFranchiseeApplication,
            'hasPendingApplication' => (bool) $hasPendingApplication,
        ]);
    }

    public function handleApplication($start = false)
    {
        $this->checkUserPermission('franchisees', false);

        $user = User::find(auth()->user()->id);

        if ($start) {
            $franchisee = $user->pendingFranchiseeApplication;
            $this->franchiseeService->delete($franchisee);
        }

        if (! $user->pendingFranchiseeApplication || $start) {
            $franchisee = $this->franchiseeService->createApplication();
        } else {
            $franchisee = $user->pendingFranchiseeApplication;
        }

        return redirect()
            ->route('franchisees.continueApplication', ['id' => $franchisee->id]);
    }

    public function continueApplication($id)
    {
        $this->checkUserPermission('franchisees', false);

        $user = auth()->user();

        $franchisee = $user->pendingFranchiseeApplication;

        if (! $franchisee) {
            $franchisee = Franchisee::find($id);

            if (! $franchisee || $franchisee?->application_step !== 'finished') {
                return redirect()->route('franchisees')->with('error', 'Invalid franchisee state.');
            }
        } elseif ($franchisee->id != $id) {
            return redirect()->route('franchisees')->with('error', 'No pending franchisee data found.');
        }

        return Inertia::render('Admin/Franchisees/Create', [
            'franchisee' => $this->franchiseeService->getInformation($franchisee),
        ]);
    }

    public function show(Franchisee $franchisee)
    {
        $this->checkUserPermission('franchisees');

        if ($franchisee->is_draft) {
            return redirect()->route('franchisees');
        }

        return Inertia::render('Admin/Franchisees/Show', [
            'activities' => $this->getActivities($franchisee->id, 'franchisee', null, 5),
            'franchisee' => $this->franchiseeService->getInformation($franchisee),
        ]);
    }

    public function edit(Franchisee $franchisee): InertiaResponse
    {
        $this->checkUserPermission('franchisees', false);

        return Inertia::render('Admin/Franchisees/Edit', [
            'franchisee' => $this->franchiseeService->getInformation($franchisee),
        ]);
    }

    public function update(UpdateFranchiseeRequest $request, Franchisee $franchisee)
    {
        $this->checkUserPermission('franchisees', false);

        $validated = $request->validated();

        $this->franchiseeService->update($validated, $franchisee);

        if ($request->has('auto_save')) {
            return response()->noContent();
        }

        $redirect = redirect()->back();

        if (! $franchisee->is_draft) {
            $redirect->with('success', __('alert.store.update.success'));
        }

        return $redirect;
    }

    public function cancelApplication(Franchisee $franchisee)
    {
        $this->franchiseeService->delete($franchisee);

        return redirect()->route('franchisees');
    }

    public function delete(Franchisee $franchisee)
    {
        $this->checkUserPermission('franchisees', false);

        $this->franchiseeService->delete($franchisee);

        return redirect()->route('franchisees')->with('success', __('alert.franchisee.delete.success'));
    }

    public function activate(Franchisee $franchisee)
    {
        $this->checkUserPermission('franchisees', false);

        $status = FranchiseeStatusEnum::Active()->value;
        $this->franchiseeService->updateStatus($franchisee, $status, 'franchisees.activate');

        return redirect()->back()->with('success', __('alert.franchisee.activate.success'));
    }

    public function deactivate(Franchisee $franchisee)
    {
        $this->checkUserPermission('franchisees', false);

        $status = FranchiseeStatusEnum::Inactive()->value;
        $this->franchiseeService->updateStatus($franchisee, $status, 'franchisees.deactivate');

        return redirect()->back()->with('success', __('alert.franchisee.deactivate.success'));
    }

    public function getDataTable(): JsonResponse
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int) request('perPage', 10);

        $data = $this->franchiseeService->getDataTable($filters, $orders, $perPage);

        return response()->json($data);
    }

    public function getQuickDetails(Franchisee $franchisee)
    {
        return response()->json($this->franchiseeService->getQuickInformation($franchisee));
    }

    public function getDataList(Request $request): array
    {
        return $this->franchiseeService->getDataList($request?->field);
    }

    public function getActivityDataTable(): JsonResponse
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int) request('perPage', 10);

        $data = $this->franchiseeService->getActivityDataTable($filters, $orders, $perPage);

        return response()->json($data);
    }
}
