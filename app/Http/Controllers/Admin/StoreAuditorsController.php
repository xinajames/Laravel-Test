<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoreAuditorRequest;
use App\Models\StoreAuditor;
use App\Services\StoreAuditorService;
use Illuminate\Http\JsonResponse;

class StoreAuditorsController extends Controller
{
    public function __construct(
        private StoreAuditorService $storeAuditorService
    ) {}

    public function store(StoreStoreAuditorRequest $request)
    {
        $validatedData = $request->validated();

        $this->storeAuditorService->store($validatedData);

        return redirect()->back()->with('success', __('alert.storeAuditor.store.success'));
    }

    public function delete(StoreAuditor $storeAuditor)
    {
        $this->storeAuditorService->delete($storeAuditor);

        return redirect()->back()->with('success', __('alert.storeAuditor.delete.success'));
    }

    public function getDataTable(): JsonResponse
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);

        $data = $this->storeAuditorService->getDataTable($filters, $orders);

        return response()->json($data);
    }

    public function getDataList($id): array
    {
        return $this->storeAuditorService->getAll($id);
    }
}
