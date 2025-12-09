<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportOptionEnum;
use App\Http\Controllers\Controller;
use App\Services\UserReportRequestService;
use App\Services\UserService;
use App\Traits\HasUserPermissions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    use HasUserPermissions;

    public function __construct(
        private UserReportRequestService $userReportRequestService,
        private UserService $userService,
    ) {}

    public function index()
    {
        $this->checkUserPermission('reports');

        return Inertia::render('Admin/Reports/Index', [
            'reportOptions' => $this->userReportRequestService->getReportOptions(),
        ]);
    }

    public function generate(Request $request)
    {
        $this->checkUserPermission('reports');

        $reportType = (int) $request->input('report_type');

        $region = $this->normalizeFilter($request->input('region'));
        $storeGroup = $this->normalizeFilter($request->input('store_group'));
        $salesType = $this->normalizeFilter($request->input('sales_type'));
        $storeStatus = $this->normalizeFilter($request->input('store_status'));
        $year = $request->input('year');

        $filterData = match ($reportType) {
            ReportOptionEnum::GanvioReport()->value => [
                'region' => $region,
                'store_group' => $storeGroup,
            ],
            ReportOptionEnum::BarangayWithJbs()->value => [
                'region' => $region,
                'store_group' => $storeGroup,
            ],
            ReportOptionEnum::Renewals()->value => [
                'region' => $region,
                'store_status' => $storeStatus,
                'year' => $year,
            ],
            ReportOptionEnum::JBSSalesPerformance()->value => [
                'region' => $region,
                'sales_type' => $salesType,
                'store_group' => $request->input('store_group') === 'All' ? null : $request->input('store_group'),
                'year' => $request->input('year') ?: now()->year,
            ],
            ReportOptionEnum::FranchiseeReport()->value => [
                'region' => $region,
            ],
            ReportOptionEnum::InsuranceReport()->value => [
                'region' => $region,
                'store_group' => $storeGroup,
                'year' => $year,
            ],
            ReportOptionEnum::ContractOfLeaseReport()->value => [
                'region' => $region,
                'store_group' => $storeGroup,
                'year' => $year,
            ],
            default => [
                'region' => $region,
            ],
        };

        $this->userReportRequestService->requestReport([
            'user_id' => auth('web')->id(),
            'report_type' => $reportType,
            'report_name' => ReportOptionEnum::getDescription($reportType),
            'filter_data' => json_encode($filterData),
        ]);

        return redirect()->back()->with('success', __('alert.report.request.success'));
    }

    private function normalizeFilter(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value === 'All' ? null : $value;
    }
}
