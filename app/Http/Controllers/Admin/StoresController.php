<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Requests\AddStoreHistoryRequest;
use App\Http\Requests\AddCoordinatedStoreHistoryRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\PhotoService;
use App\Services\ReminderInstanceService;
use App\Services\ReminderService;
use App\Services\StoreHistoryService;
use App\Services\StoreRatingService;
use App\Services\StoreService;
use App\Traits\HasAuditorAccess;
use App\Traits\HasUserPermissions;
use App\Traits\ManageActivities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StoresController extends Controller
{
    use HasAuditorAccess;
    use HasUserPermissions;
    use ManageActivities;

    public function __construct(
        private PhotoService $photoService,
        private ReminderService $reminderService,
        private ReminderInstanceService $reminderInstanceService,
        private StoreService $storeService,
        private StoreHistoryService $storeHistoryService,
        private StoreRatingService $storeRatingService,
    ) {}

    public function index(): InertiaResponse
    {
        $this->checkUserPermission('stores');

        $user = auth()->user();

        $hasPendingCreation = $user->pendingStoreApplication;
        $ongoingStoreRatings = $user->ongoingStoreRating->map(function ($storeRating) {
            return $this->storeRatingService->getInformation($storeRating);
        });

        return Inertia::render('Admin/Stores/Index', [
            'hasPendingCreation' => (bool) $hasPendingCreation,
            'ongoingStoreRatings' => $ongoingStoreRatings,
        ]);
    }

    public function handleCreate(Request $request): RedirectResponse
    {
        $user = User::find(auth()->user()->id);

        $input = $request->all();

        $start = $request->boolean('start');

        if ($start) {
            $store = $user->pendingStoreApplication;
            $this->storeService->delete($store);
        }

        if (! $user->pendingStoreApplication || $start) {
            $store = $this->storeService->createStore($input);
        } else {
            $store = $user->pendingStoreApplication;
        }

        if (! $store->is_draft) {
            $this->reminderService->generateStoreReminderInstance($store);
        }

        return redirect()
            ->route('stores.continue', ['id' => $store->id]);
    }

    public function delete(Store $store)
    {
        $this->checkUserPermission('stores', false);

        $this->storeService->delete($store);

        return redirect()->route('stores')->with('success', __('alert.store.delete.success'));
    }

    public function continue($id): InertiaResponse|RedirectResponse
    {
        $this->checkUserPermission('stores', false);

        $user = auth()->user();

        $store = $user->pendingStoreApplication;

        if (! $store) {
            $store = Store::find($id);

            if (! $store || $store?->application_step !== 'finished') {
                return redirect()->route('stores')->with('error', 'Invalid store state.');
            }
        } elseif ($store->id != $id) {
            return redirect()->route('stores')->with('error', 'No pending store data found.');
        }

        return Inertia::render('Admin/Stores/Create', [
            'store' => $this->storeService->getInformation($store),
        ]);
    }

    public function show(Store $store)
    {
        $this->checkUserPermission('stores');

        $user = auth()->user();

        if ($user->isUserAuditor() && ! $this->hasStoreAuditorAccess($user, $store->id)) {
            abort(403, 'Unauthorized action.');
        }

        if ($store->is_draft) {
            return redirect()->route('stores');
        }

        $ongoingStoreRating = $user->ongoingStoreRating->where('store_id', $store->id)->first();

        return Inertia::render('Admin/Stores/Show', [
            'activities' => $this->getActivities($store->id, 'store', null, 5),
            'store' => $this->storeService->getInformation($store),
            'ongoingStoreRating' => $ongoingStoreRating,
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store)
    {
        $this->checkUserPermission('stores', false);

        $validated = $request->validated();

        $this->storeService->update($validated, $store);

        $redirect = redirect()->back();

        if (! $store->is_draft) {
            $redirect->with('success', __('alert.store.update.success'));
        }

        return $redirect;
    }

    public function cancelCreate(Store $store)
    {
        $this->storeService->delete($store);

        return redirect()->route('stores');
    }

    public function activate(Store $store)
    {
        $this->checkUserPermission('stores', false);

        $this->storeService->updateIsActive($store, 'stores.activate');

        return redirect()->back()->with('success', __('alert.store.activate.success'));
    }

    public function deactivate(Store $store)
    {
        $this->checkUserPermission('stores', false);

        $this->storeService->updateIsActive($store, 'stores.deactivate', false);

        return redirect()->back()->with('success', __('alert.store.deactivate.success'));
    }

    public function getDataTable(): JsonResponse
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int) request('perPage', 10);

        $user = auth()->user();
        $auditorId = $user->isUserAuditor() ? $user->id : null;

        $data = $this->storeService->getDataTable($filters, $orders, $perPage, $auditorId);

        return response()->json($data);
    }

    public function edit(Store $store): InertiaResponse
    {
        return Inertia::render('Admin/Stores/Edit', [
            'store' => $this->storeService->getInformation($store),
        ]);
    }

    public function getStoreHistory(Store $store, Request $request)
    {
        $field = $request->query('field');
        
        // Handle multiple fields separated by comma for coordinated fields
        if ($field && str_contains($field, ',')) {
            $fields = explode(',', $field);
            $histories = [];
            foreach ($fields as $singleField) {
                $fieldHistory = $this->storeHistoryService->getHistory($store, trim($singleField));
                $histories = array_merge($histories, $fieldHistory['history']->toArray());
            }
            
            // Sort by created_at desc
            usort($histories, function($a, $b) {
                $aTime = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
                $bTime = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
                return $bTime - $aTime;
            });
            
            return response()->json([
                'store_id' => $store->id,
                'history' => $histories
            ]);
        }

        return response()->json($this->storeHistoryService->getHistory($store, $field));
    }

    public function addStoreHistory(AddStoreHistoryRequest $request, Store $store)
    {
        $this->checkUserPermission('stores', false);

        $validated = $request->validated();
        
        // Transform the data structure for the service
        $historyData = [
            'field' => $validated['field'],
            'old_value' => null, // Always null since we're only adding history data
            'new_value' => $validated['value'],
            'effective_at' => $validated['effective_at'],
        ];

        $this->storeHistoryService->addHistoryEntry($store, $historyData, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Store history entry added successfully.'
        ]);
    }

    public function addCoordinatedStoreHistory(AddCoordinatedStoreHistoryRequest $request, Store $store)
    {
        $this->checkUserPermission('stores', false);

        $validated = $request->validated();
        
        $this->storeHistoryService->addCoordinatedHistoryEntry($store, $validated, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Coordinated store history entries added successfully.'
        ]);
    }

    public function getStoreRatings(Store $store, $lastIndex)
    {
        return response()->json($this->storeRatingService->getStoreRatings($store, $lastIndex, 5));
    }

    public function getStoreNotification(Store $store)
    {
        return response()->json($this->reminderInstanceService->getStoreNotifications($store->id));
    }

    public function getDataList(Request $request): array
    {
        return $this->storeService->getDataList($request?->field);
    }

    public function getActivityDataTable(): JsonResponse
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int) request('perPage', 10);

        $data = $this->storeService->getActivityDataTable($filters, $orders, $perPage);

        return response()->json($data);
    }
}
