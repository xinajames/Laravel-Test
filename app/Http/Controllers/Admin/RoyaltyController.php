<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Http\Controllers\Controller;
use App\Jobs\Royalty\CacheUploadedRoyaltyFilesJob;
use App\Jobs\Royalty\GenerateSalesHistoryJob;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroBatchConfig;
use App\Models\Royalty\MacroFixedCache;
use App\Models\Royalty\MacroOutput;
use App\Models\Royalty\MacroUpload;
use App\Models\SalesPerformance;
use App\Services\RoyaltyService;
use App\Traits\HasUserPermissions;
use App\Traits\ManageFilesystems;
use App\Traits\ManageRoyaltyFiles;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RoyaltyController extends Controller
{
    use HasUserPermissions;
    use ManageFilesystems, ManageRoyaltyFiles;

    public function __construct(
        private RoyaltyService $royaltyService,
    )
    {
    }

    public function index()
    {
        $this->checkUserPermission('royalty');

        $generatingBatches = $this->fetchGeneratingRoyaltyBatches()->map(function ($batch) {
            return [
                'id' => $batch->id,
                'title' => $batch->title,
                'status' => 'Generating...',
            ];
        });

        return Inertia::render('Admin/Royalty/Index', [
            'generatingBatches' => $generatingBatches,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string',
            'title' => 'required|string',
            'generate_files' => 'required|array|min:1',
            'generate_files.*' => 'in:1,2', // 1 = MNSR, 2 = RWB
            'mnsr.*.files.*' => 'file|mimes:csv,txt,xlsx,xls,pdf',
            'jbms.*.files.*' => 'file|mimes:csv,txt,xlsx,xls,pdf',
            'pos.*.files.*' => 'file|mimes:csv,txt,xlsx,xls,pdf',
            'zen.*.files.*' => 'file|mimes:csv,txt,xlsx,xls,pdf',
        ]);

        $generateFiles = collect($request->input('generate_files', []));
        $shouldGenerateMNSR = $generateFiles->contains(1);
        $shouldGenerateRWB = $generateFiles->contains(2);

        // Validate that at least one file type is selected for generation
        if (!$shouldGenerateMNSR && !$shouldGenerateRWB) {
            return redirect()
                ->back()
                ->withErrors(['generate_files' => 'Please select at least one file type to generate.'])
                ->withInput();
        }

        $mnsrFileNames = collect($request->file('mnsr', []))
            ->flatMap(function ($item) {
                return collect($item['files'] ?? [])->map(function ($file) {
                    return $file->getClientOriginalName();
                });
            })
            ->all();

        $jbmsFileNames = collect($request->file('jbms', []))
            ->flatMap(function ($item) {
                return collect($item['files'] ?? [])->map(function ($file) {
                    return $file->getClientOriginalName();
                });
            })
            ->all();

        $posFileNames = collect($request->file('pos', []))
            ->flatMap(function ($item) {
                return collect($item['files'] ?? [])->map(function ($file) {
                    return $file->getClientOriginalName();
                });
            })
            ->all();

        // Always require JBMIS and POS files
        if (empty($jbmsFileNames)) {
            return redirect()
                ->back()
                ->withErrors(['jbms' => 'JBMIS files are required.'])
                ->withInput();
        }

        if (empty($posFileNames)) {
            return redirect()
                ->back()
                ->withErrors(['pos' => 'POS files are required.'])
                ->withInput();
        }

        $this->validateUploads($mnsrFileNames, $jbmsFileNames, $posFileNames, $shouldGenerateMNSR);

        $allFiles = collect();

        foreach (['mnsr', 'jbms', 'pos', 'zen'] as $type) {
            if ($request->has($type)) {
                foreach ($request->$type as $group) {
                    if (isset($group['files'])) {
                        foreach ($group['files'] as $file) {
                            $file_name = $file->getClientOriginalName();
                            $monthYear = $this->detectMonthYearFromFile($file_name);

                            $allFiles->push([
                                'file' => $file,
                                'type' => $type,
                                'file_name' => $file_name,
                                'month_year' => $monthYear,
                            ]);
                        }
                    }
                }
            }
        }

        // group files by month-year
        $filesGrouped = $allFiles->groupBy('month_year');

        DB::beginTransaction();

        foreach ($filesGrouped as $monthYear => $files) {
            $macroBatch = new MacroBatch;
            $macroBatch->code = Str::uuid();
            $macroBatch->user_id = auth()->user()->id;
            $macroBatch->title = "{$request->title} [{$monthYear}]";
            $macroBatch->remarks = $request->remarks;
            $macroBatch->save();

            $hasUploadedMNSR = false;

            foreach ($files as $fileInfo) {
                $file = $fileInfo['file'];
                $file_name = $fileInfo['file_name'];

                $file_type = $this->getUploadedFileType($file_name);
                $upload_path = $this->generateUploadBasePath() . '/royalty/uploads/' . $macroBatch->id . '/' . $file_name;

                $macroUpload = new MacroUpload;
                $macroUpload->batch_id = $macroBatch->id;
                $macroUpload->file_name = $file_name;
                $macroUpload->file_type_id = $file_type->value;
                $macroUpload->file_path = $upload_path;
                $macroUpload->file_size = $file->getSize();
                $macroUpload->save();

                if ($file_type->value == MacroFileTypeEnum::JBMISData()->value) {
                    $parts = explode('-', $macroUpload->file_name);
                    $monthAbbrev = $parts[3];
                    $month = Carbon::createFromFormat('M', $monthAbbrev)->month;

                    $yearWithExt = $parts[4];
                    $year = pathinfo($yearWithExt, PATHINFO_FILENAME); // strips file extension

                    $macroBatch->month = $month;
                    $macroBatch->year = $year;
                    $macroBatch->save();
                } elseif ($file_type->value == MacroFileTypeEnum::MNSR()->value) {
                    $hasUploadedMNSR = true;

                    $parts = explode('-', $macroUpload->file_name);
                    $monthAbbrev = $parts[4]; // Monthly-Natl-Sales-Rept-MMM-YYYY
                    $month = Carbon::createFromFormat('M', $monthAbbrev)->month;

                    $yearWithExt = $parts[5];
                    $year = pathinfo($yearWithExt, PATHINFO_FILENAME); // strips file extension

                    $macroBatch->month = $month;
                    $macroBatch->year = $year;
                    $macroBatch->save();
                }

                $uploadSuccess = $this->upload($file, $upload_path);
                if (!$uploadSuccess) {
                    throw new Exception('Failed to store uploaded royalty file');
                }
            }

            $macroBatchConfig = new MacroBatchConfig();
            $macroBatchConfig->batch_id = $macroBatch->id;
            $macroBatchConfig->gen_mnsr = $shouldGenerateMNSR;
            $macroBatchConfig->gen_rwb = $shouldGenerateRWB;
            $macroBatchConfig->has_uploaded_mnsr = $hasUploadedMNSR;
            $macroBatchConfig->save();

            // dispatch after all uploads for the batch
            dispatch(new CacheUploadedRoyaltyFilesJob($macroBatch->id));
        }

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Royalty generation started.');
    }

    public function generateSalesHistory(Request $request)
    {
        $request->validate([
            'sales_month' => 'required|integer|min:1|max:12',
            'sales_year' => 'required|integer|min:2000|max:2100',
        ]);

        $month = $request->input('sales_month');
        $year = $request->input('sales_year');

        // Create month name for title
        $monthName = Carbon::createFromFormat('n', $month)->format('M');

        DB::beginTransaction();

        try {
            // Create MacroBatch
            $macroBatch = new MacroBatch;
            $macroBatch->code = Str::uuid();
            $macroBatch->user_id = auth()->user()->id;
            $macroBatch->title = "Update Sales History [{$monthName} - {$year}]";
            $macroBatch->remarks = null;
            $macroBatch->month = $month;
            $macroBatch->year = $year;
            $macroBatch->save();

            dispatch(new GenerateSalesHistoryJob($macroBatch->id));

            DB::commit();

            return redirect()
                ->back()
                ->with('success', 'Sales history generated successfully for {$monthName} {$year}.');
        } catch (\Throwable $e) {
            DB::rollBack();

            \Illuminate\Support\Facades\Log::error('Sales history generation failed', [
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('success', 'Sales history generation failed for {$monthName} {$year}.');
        }
    }

    protected function validateUploads(array $mnsrFileNames, array $jbmsFileNames, array $posFileNames, bool $shouldGenerateMNSR)
    {
        // Validate MNSR files if uploaded
        $mnsrDates = collect();
        if (!empty($mnsrFileNames)) {
            $mnsrDates = collect($mnsrFileNames)->map(function ($filename) {
                if (!preg_match('/Monthly-Natl-Sales-Rept-([A-Za-z]{3})-(\d{4})/i', pathinfo($filename, PATHINFO_FILENAME), $matches)) {
                    $this->failValidation("Invalid MNSR filename format: {$filename}. Expected format: Monthly-Natl-Sales-Rept-MMM-YYYY.xlsx");
                }

                return $this->parseMonthYear("{$matches[1]}-{$matches[2]}");
            });
        } elseif (!$shouldGenerateMNSR && empty($mnsrFileNames)) {
            $this->failValidation('MNSR file is required when MNSR generation is not selected.');
        }

        $jbmsDates = collect($jbmsFileNames)->map(function ($filename) {
            if (!preg_match('/JBMIS-Data-(.*)/i', pathinfo($filename, PATHINFO_FILENAME), $matches)) {
                $this->failValidation("Invalid JBMIS filename format: {$filename}");
            }

            return $this->parseRegionMonthYear($matches[1]);
        });

        $posDates = collect($posFileNames)->map(function ($filename) {
            if (!preg_match('/POS-Data-[^-]+-([A-Za-z]{3}-\d{4})/i', pathinfo($filename, PATHINFO_FILENAME), $matches)) {
                $this->failValidation("Invalid POS filename format: {$filename}");
            }

            return $this->parseMonthYear($matches[1]);
        });

        $allDates = $jbmsDates->merge($posDates)->merge($mnsrDates)->sort();
        if ($jbmsDates->isEmpty() || $posDates->isEmpty()) {
            $this->failValidation('JBMIS and POS files are required.');
        }

        $minDate = $allDates->min();
        $maxDate = $allDates->max();

        if ($minDate->diffInMonths($maxDate) > 12) {
            $this->failValidation('Uploaded files span more than 12 months.');
        }

        // Group dates by month-year for validation
        $jbmsGrouped = $jbmsDates->groupBy(fn($date) => $date->format('Y-m'));
        $mnsrGrouped = $mnsrDates->groupBy(fn($date) => $date->format('Y-m'));

        foreach ($jbmsGrouped as $monthYear => $group) {
            if ($group->count() < 1) {
                return $this->failValidation("Missing JBMIS file for {$monthYear}.");
            }

            if (!$posDates->contains(fn($posDate) => $posDate->format('Y-m') == $monthYear)) {
                return $this->failValidation("Missing POS file for {$monthYear}.");
            }

            // If MNSR is not being generated and no MNSR files uploaded, require MNSR file
            if (!$shouldGenerateMNSR && !$mnsrGrouped->has($monthYear)) {
                return $this->failValidation("Missing MNSR file for {$monthYear}.");
            }
        }

        return true;
    }

    private function failValidation(string $message)
    {
        return redirect()
            ->back()
            ->with('error', $message)
            ->withInput()
            ->throwResponse();
    }

    protected function parseRegionMonthYear(string $regionMonthYear): \Carbon\Carbon
    {
        $parts = explode('-', $regionMonthYear);

        if (count($parts) < 2) {
            $this->failValidation("Invalid Region-Month-Year format: {$regionMonthYear}");
        }

        $month = $parts[count($parts) - 2];
        $year = $parts[count($parts) - 1];

        return \Carbon\Carbon::createFromFormat('M-Y', "{$month}-{$year}")->startOfMonth();
    }

    protected function parseMonthYear(string $monthYear): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('M-Y', $monthYear)->startOfMonth();
    }

    protected function detectMonthYearFromFile(string $fileName): string
    {
        $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

        if (str_starts_with($nameWithoutExtension, 'Monthly-Natl-Sales-Rept-')) {
            if (preg_match('/Monthly-Natl-Sales-Rept-([A-Za-z]{3})-(\d{4})/i', $nameWithoutExtension, $matches)) {
                return $this->parseMonthYear("{$matches[1]}-{$matches[2]}")->format('Y-m');
            }
        }

        if (str_starts_with($nameWithoutExtension, 'JBMIS-Data-')) {
            if (preg_match('/JBMIS-Data-(.*)/i', $nameWithoutExtension, $matches)) {
                return $this->parseRegionMonthYear($matches[1])->format('Y-m');
            }
        }

        if (str_starts_with($nameWithoutExtension, 'POS-Data-')) {
            if (preg_match('/POS-Data-[^-]+-([A-Za-z]{3}-\d{4})/i', $nameWithoutExtension, $matches)) {
                return $this->parseMonthYear($matches[1])->format('Y-m');
            }
        }

        if (str_starts_with($nameWithoutExtension, 'ZEN-Data-')) {
            // (Optional) Add ZEN parsing if needed later
        }

        throw new Exception("Unable to detect Month-Year from filename: {$fileName}");
    }

    private function fetchGeneratingRoyaltyBatches()
    {
        return MacroBatch::where('status', MacroBatchStatusEnum::Ongoing())
            ->where('user_id', auth()->user()->id)
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'status']);
    }

    public function getGeneratingRoyaltyBatches()
    {
        return response()->json(
            $this->fetchGeneratingRoyaltyBatches()->map(fn($batch) => [
                'id' => $batch->id,
                'title' => $batch->title,
                'status' => 'Generating...',
            ])
        );
    }

    private function generateDefaultRoyaltyOutputs($batch_id, $monthAbbrev, $year)
    {
        $filesToGenerate = [
            [
                'file_name' => 'Monthly-Natl-Sales-Rept-' . $monthAbbrev . '-' . $year . '.xlsx',
                'file_type_id' => MacroFileTypeEnum::MNSR()->value,
                'file_revision_id' => MacroFileRevisionEnum::MNSRDefault()->value,
            ],
        ];
    }

    public function getDataTable(): JsonResponse
    {
        if (!request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int)request('perPage', 10);

        $data = $this->royaltyService->getDataTable($filters, $orders, $perPage);

        return response()->json($data);
    }

    public function downloadUpload($id)
    {
        $upload = MacroUpload::findOrFail($id);
        $path = $upload->file_path;

        return $this->downloadFile($path, $upload->file_name);
    }

    public function downloadOutput($id)
    {
        $output = MacroOutput::findOrFail($id);
        $path = $output->file_path;

        return $this->downloadFile($path, $output->file_name);
    }

    public function invalidate($id)
    {
        $this->checkUserPermission('royalty');

        try {
            DB::beginTransaction();

            $macroBatch = MacroBatch::withTrashed()->findOrFail($id);

            // Check if already soft deleted
            if ($macroBatch->trashed()) {
                return redirect()
                    ->back()
                    ->with('error', 'This royalty has already been invalidated.');
            }

            // Check if status allows invalidation (only successful or failed)
            $status = $macroBatch->status;
            if ($status != MacroBatchStatusEnum::Successful()->value && $status != MacroBatchStatusEnum::Failed()->value) {
                return redirect()
                    ->back()
                    ->with('error', 'Only successful or failed royalties can be invalidated.');
            }

            // Set status to Failed and soft delete the MacroBatch
            $macroBatch->status = MacroBatchStatusEnum::Failed()->value;
            $macroBatch->save();
            $macroBatch->delete(); // Soft delete

            // Mark all related MacroOutput records as failed
            MacroOutput::where('batch_id', $id)
                ->update(['status' => MacroBatchStatusEnum::Failed()->value]);

            // Find and soft delete SalesPerformance records that belong specifically to this batch
            // Only delete sales performance data generated by the "Generate Sales History" feature
            // which creates files with paths like: /jform-{env}-filesystem/royalty/generated/{batch_id}/JBS-Sales-History-*.xlsx
            // Use environment-agnostic pattern to handle imported data from different environments
            $pathPattern = '/jform-%-filesystem/royalty/generated/' . $id . '/%';

            $salesPerformanceRecords = SalesPerformance::where('path', 'LIKE', $pathPattern)
                ->whereNull('deleted_at')
                ->get();

            foreach ($salesPerformanceRecords as $salesPerformance) {
                $salesPerformance->delete(); // Soft delete
            }

            DB::commit();

            return redirect()
                ->back()
                ->with('success', 'Royalty has been successfully invalidated.');
        } catch (Exception $e) {
            DB::rollBack();

            \Illuminate\Support\Facades\Log::error('Royalty invalidation failed', [
                'batch_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to invalidate royalty. Please try again.');
        }
    }
}
