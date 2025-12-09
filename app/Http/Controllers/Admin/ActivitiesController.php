<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ActivitiesController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}

    public function index()
    {
        $user = Auth::user();

        if (! $user->hasUserRoleType('Super Admin')) {
            abort(403, 'Access denied. This page is only available for Super Admin users.');
        }

        return Inertia::render('Admin/Activities/Index');
    }

    public function getDataTable(): JsonResponse
    {
        $user = Auth::user();

        if (! $user->hasUserRoleType('Super Admin')) {
            return response()->json(['error' => 'Access denied. This functionality is only available for Super Admin users.'], 403);
        }

        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int) request('perPage', 10);

        $data = $this->activityLogService->getDataTable($filters, $orders, $perPage);

        return response()->json($data);
    }

    public function getDataList(Request $request): array
    {
        $user = Auth::user();

        if (! $user->hasUserRoleType('Super Admin')) {
            abort(403, 'Access denied. This functionality is only available for Super Admin users.');
        }

        return $this->activityLogService->getDataList($request?->field);
    }
}
