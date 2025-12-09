<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FranchiseeService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private FranchiseeService $franchiseeService,
        private StoreService $storeService,
    ) {}

    public function index()
    {
        return Inertia::render('Admin/Dashboard/Index');
    }

    public function getFranchiseeRegionDetails()
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $data = $this->franchiseeService->getFranchiseeRegionDetails();

        return response()->json($data);
    }

    public function getFranchiseeCountDetails()
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $data = $this->franchiseeService->getFranchiseeCountDetails();

        return response()->json($data);
    }

    public function getStoreOpeningClosures(Request $request)
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = $request->only([
            'region',
            'date_year',
            'store_group',
            'date_field',
        ]);

        $data = $this->storeService->getStoreOpeningClosures($filters);

        return response()->json($data);
    }

    public function getStoreTemporaryClosures(Request $request)
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = $request->only([
            'region',
            'date_year',
            'store_group',
        ]);

        $data = $this->storeService->getStoreTemporaryClosures($filters);

        return response()->json($data);
    }
}
