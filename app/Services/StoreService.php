<?php

namespace App\Services;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreInsuranceTypeEnum;
use App\Enums\StoreStatusEnum;
use App\Enums\StoreTypeEnum;
use App\Enums\StoreWarehouseEnum;
use App\Helpers\DateHelper;
use App\Models\Activity;
use App\Models\Store;
use App\Support\Filters\FuzzyFilter;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;
use App\Traits\ManageFilesystems;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StoreService
{
    use HandleTransactions;
    use ManageActivities;
    use ManageFilesystems;

    public function createStore(array $storeData = [], $user = null)
    {
        return $this->transact(function () use ($storeData) {
            return Store::create($storeData);
        });
    }

    public function update(array $storeData, Store $store, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($storeData, $store, $user) {
            $photoService = new PhotoService;
            $storeHistoryService = new StoreHistoryService;

            $dateFields = [
                'date_opened',
                'franchise_date',
                'original_franchise_date',
                'renewal_date',
                'last_renewal_date',
                'effectivity_date',
                'target_opening_date',
                'soft_opening_date',
                'grand_opening_date',
                'cctv_installed_at',
                'internet_installed_at',
                'pos_installed_at',
                'cgl_expiry_date',
                'fire_expiry_date',
                'contract_of_lease_start_date',
                'contract_of_lease_end_date',
                'lease_payment_date',
                'col_notarized_date',
                'maintenance_last_repaint_at',
                'maintenance_last_renovation_at',
                'maintenance_temporary_closed_at',
                'maintenance_reopening_date',
                'maintenance_permanent_closure_date',
                'maintenance_upgrade_date',
                'maintenance_downgrade_date',
                'maintenance_store_acquired_at',
                'maintenance_store_transferred_at',
                'continuing_license_fee_in_effect',
            ];

            $numberFields = [
                'sales_per_capita',
                'projected_peso_bread_sales_per_month',
                'projected_peso_non_bread_sales_per_month',
                'projected_total_cost',
            ];

            foreach ($dateFields as $field) {
                if (! empty($storeData[$field])) {
                    $dateStr = $storeData[$field];

                    // Check if the date is already in Y-m-d format (e.g., "2025-04-18")
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                        $format = 'Y-m-d';
                    } // Otherwise assume the date is in m-d-Y format (e.g., "04-18-2025")
                    elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateStr)) {
                        $format = 'm-d-Y';
                    } else {
                        continue;
                    }

                    $storeData[$field] = Carbon::createFromFormat($format, $dateStr)->format('Y-m-d');
                }
            }

            foreach ($numberFields as $field) {
                if (! empty($storeData[$field])) {
                    $storeData[$field] = preg_replace('/[^0-9-.,]+/', '', $storeData[$field]);
                    $storeData[$field] = str_replace(',', '', $storeData[$field]);
                }
            }

            if (isset($storeData['warehouse']) && $storeData['warehouse'] !== 'Others') {
                $storeData['custom_warehouse_name'] = null;
            }

            if (! $store->is_draft) {
                $trackedFields = $storeHistoryService->getTrackedFields();
                $filteredStoreData = array_intersect_key($storeData, array_flip($trackedFields));

                $storeHistoryService->logChanges($store, $filteredStoreData, $user);
            }

            $store->update($storeData);

            if (! empty($storeData['photos'])) {

                foreach ($storeData['photos'] as $photoData) {

                    if ($photoData instanceof UploadedFile) {
                        $data = [
                            'file_name' => pathinfo($photoData->getClientOriginalName(), PATHINFO_FILENAME).'-'.uniqid(),
                            'file_size' => $photoData->getSize(),
                        ];
                        $photoService->createPhoto($photoData, 'store', $store->id, $data);
                    }
                    // Check if a file was uploaded
                    elseif (! empty($photoData['file']) && is_file($photoData['file'])) {
                        $basePath = $this->generateUploadBasePath();
                        $fileName = $photoData['file']->getClientOriginalName();
                        $newFilePath = "{$basePath}/store/{$store->id}/photos/{$fileName}";

                        // If updating an existing photo
                        if (! empty($photoData['id'])) {
                            $photo = $store->photos()->find($photoData['id']);
                            if ($photo && ! empty($photo->file_path)) {
                                $this->deleteFile($photo->file_path);
                            }
                            $uploadDisk = $this->getDefaultUploadDisk();
                            $this->upload($photoData['file'], $newFilePath, $uploadDisk);
                            $photo->update([
                                'file_path' => $newFilePath,
                                'disk' => $uploadDisk,
                                'description' => $photoData['description'] ?? '',
                            ]);
                        } else {
                            // Adding a new photo
                            $uploadDisk = $this->getDefaultUploadDisk();
                            $this->upload($photoData['file'], $newFilePath, $uploadDisk);
                            $store->photos()->create([
                                'file_path' => $newFilePath,
                                'disk' => $uploadDisk,
                                'description' => $photoData['description'] ?? '',
                            ]);
                        }
                    } elseif (isset($photoData['description']) && ! empty($photoData['id'])) {
                        // If only updating the caption
                        $photo = $store->photos()->find($photoData['id']);
                        if ($photo) {
                            $photo->update([
                                'description' => $photoData['description'],
                            ]);
                        }
                    }
                }
            }

            // Handle deletion of photos based on their IDs
            if (! empty($storeData['delete_photo_id'])) {
                // Ensure we always have an array, even if a single ID is passed
                $photoIdsToDelete = is_array($storeData['delete_photo_id']) ? $storeData['delete_photo_id'] : [$storeData['delete_photo_id']];

                foreach ($photoIdsToDelete as $photoId) {
                    $photo = $store->photos()->find($photoId);
                    if ($photo) {
                        $this->deleteFile($photo->file_path);
                        $photo->delete();
                    }
                }
            }

            if (isset($storeData['application_step']) && $storeData['application_step'] == 'finished') {
                foreach ($storeData as $key => $documentGroup) {
                    if (Str::startsWith($key, 'documents_') && is_array($documentGroup)) {
                        foreach ($documentGroup as $file) {
                            if (isset($file['files']) && is_array($file['files'])) {
                                $basePath = $this->generateUploadBasePath();
                                $uploadedFiles = $file['files'];
                                foreach ($uploadedFiles as $uploadedFile) {
                                    $originalName = $uploadedFile->getClientOriginalName();
                                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                                    // Folder path: base/store/{store_id}/documents
                                    $storageFolder = "{$basePath}/store/{$store->id}/documents";
                                    $fileName = $originalName;
                                    $counter = 1;

                                    while (Storage::exists("{$storageFolder}/{$fileName}")) {
                                        $fileName = "{$nameOnly}_{$counter}.{$extension}";
                                        $counter++;
                                    }

                                    $fullPath = "{$storageFolder}/{$fileName}";
                                    $uploadDisk = $this->getDefaultUploadDisk();

                                    $this->upload($uploadedFile, $fullPath, $uploadDisk);

                                    $store->documents()->create([
                                        'document_name' => $fileName,
                                        'file_name' => $fileName,
                                        'file_path' => $fullPath,
                                        'disk' => $uploadDisk,
                                        'file_type' => $uploadedFile->getClientMimeType(),
                                        'file_size' => $uploadedFile->getSize(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            if (! $store->is_draft && ! array_key_exists('is_draft', $storeData)) {
                $this->log($store, 'stores.update', $user);
            } elseif (array_key_exists('is_draft', $storeData) && $storeData['is_draft'] === false) {
                // Generate app code, log when store is completely created
                if (! $store->store_code) {
                    $nextCode = $this->generateStoreCode();
                    $store->update(['store_code' => $nextCode]);
                }
                $this->log($store, 'stores.store', $user);
            }

            return $store;
        });
    }

    public function delete(Store $store)
    {
        $this->transact(function () use ($store) {
            $store->delete();

            if (! $store->is_draft) {
                $this->log($store, 'stores.delete');
            }
        });
    }

    public function updateIsActive(Store $store, $logAction, $isActive = true)
    {
        return $this->transact(function () use ($store, $logAction, $isActive) {
            $store->update(['is_active' => $isActive]);

            if (! $store->is_draft) {
                $this->log($store, $logAction);
            }

            return $store;
        });
    }

    public function updateStatus(Store $store, $status, $logAction)
    {
        return $this->transact(function () use ($store, $status, $logAction) {
            $store->update(['store_status' => $status]);

            if (! $store->is_draft) {
                $this->log($store, $logAction);
            }

            return $store;
        });
    }

    public function getDataTable($filters = [], $orders = [], $perPage = 10, $auditorId = null): LengthAwarePaginator
    {
        $query = Store::query()
            ->leftJoin('franchisees', 'stores.franchisee_id', '=', 'franchisees.id')
            ->select(
                'stores.id',
                'stores.store_code',
                'is_active as status',
                'franchisees.id as franchisee_id',
                'franchisees.first_name',
                'franchisees.middle_name',
                'franchisees.last_name',
                'franchisees.corporation_name',
                DB::raw(
                    "CONCAT(franchisees.first_name, ' ', COALESCE(franchisees.middle_name, ''), ' ', franchisees.last_name) as franchisee_name"
                ),
                'franchisees.franchisee_code',
                'stores.jbs_name',
                'stores.store_type',
                'stores.store_group',
                'stores.store_status',
                'stores.region',
                'stores.store_province',
                'stores.store_city',
                'stores.created_at',
                'stores.updated_at',
            )
            ->where('stores.is_draft', false)
            ->where('stores.is_active', true)
            ->where('stores.deleted_at', null);

        if (! is_null($auditorId)) {
            $query->whereExists(function ($q) use ($auditorId) {
                $q->select(DB::raw(1))
                    ->from('store_auditors')
                    ->whereColumn('store_auditors.store_id', 'stores.id')
                    ->where('store_auditors.user_id', $auditorId);
            });
        }

        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($filter['column'])) {
                    $columnName = $filter['column'];
                    $operator = $filter['operator'];
                    $value = $filter['value'];

                    $query->where($columnName, $operator, $value);
                }
            }
        }

        $sortColumn = 'stores.updated_at';
        if ($orders) {
            foreach ($orders as $column => $data) {
                $sortColumn = $data['column'];
                if ($data['value'] == 'desc') {
                    $sortColumn = '-'.$data['column']; // Hyphen on front means descending
                }
            }
        }

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::custom(
                    'search',
                    new FuzzyFilter(
                        'stores.jbs_name',
                        'stores.store_code',
                        'stores.store_type',
                        'stores.store_group',
                        'stores.store_status',
                        'stores.region',
                        'stores.store_province',
                        'stores.store_city',
                        'franchisees.first_name',
                        'franchisees.middle_name',
                        'franchisees.last_name',
                        'franchisees.franchisee_code',
                        'franchisees.corporation_name',
                        'franchisee_name',
                    )
                ),
            ])
            ->defaultSort($sortColumn)
            ->paginate($perPage);

        $data->getCollection()->transform(function ($store) {
            $store->store_type = StoreTypeEnum::getDescription($store->store_type);
            $store->store_group = StoreGroupEnum::getDescription($store->store_group);
            $store->store_status = StoreStatusEnum::getDescription($store->store_status);

            $store->formatted_created_at = DateHelper::changeDateTimeFormat($store->created_at);
            $store->formatted_updated_at = DateHelper::changeDateTimeFormat($store->updated_at);

            return $store;
        });

        return $data;
    }

    public function getInformation(Store $store)
    {
        /* TODO :: return only needed data */
        $store->franchisee = $store->franchisee ?? null;

        $dateFields = [
            'date_opened',
            'franchise_date',
            'original_franchise_date',
            'renewal_date',
            'last_renewal_date',
            'effectivity_date',
            'target_opening_date',
            'soft_opening_date',
            'grand_opening_date',
            'cctv_installed_at',
            'internet_installed_at',
            'pos_installed_at',
            'insurance_period_covered_date',
            'insurance_expiry_date',
            'contract_of_lease_start_date',
            'contract_of_lease_renewal_expiry_date',
            'lease_payment_date',
            'col_notarized_date',
            'maintenance_last_repaint_at',
            'maintenance_last_renovation_at',
            'maintenance_temporary_closed_at',
            'maintenance_reopening_date',
            'maintenance_permanent_closure_date',
            'maintenance_upgrade_date',
            'maintenance_downgrade_date',
            'maintenance_store_acquired_at',
            'maintenance_store_transferred_at',
        ];

        foreach ($dateFields as $field) {
            if (! empty($store->$field)) {
                $store->$field = Carbon::parse($store->$field)->format('m-d-Y');
            }
        }

        $store->store_type_label = StoreTypeEnum::getDescription($store->store_type);
        $store->store_group_label = StoreGroupEnum::getDescription($store->store_group);
        $store->store_status_label = StoreStatusEnum::getDescription($store->store_status);
        $store->insurance_type_label = StoreInsuranceTypeEnum::getDescription($store->insurance_type);
        $store->warehouse_label = StoreWarehouseEnum::getDescription($store->warehouse);

        $store->store_photos = $store->photos->map(function ($photo) {
            return [
                'id' => $photo->id,
                'preview' => $this->retrieveFile($photo->file_path, $photo->disk),
                'description' => $photo->description,
            ];
        });

        $store->recent_documents = $store->documents()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($document) {
                $document->formatted_created_at = DateHelper::changeDateTimeFormat($document->created_at);
                $document->preview = $this->retrieveFile($document->file_path, $document->disk);

                return $document;
            });

        return $store;
    }

    public function getDataList($field = null): array
    {
        return Store::when($field, function ($query) use ($field) {
            $query->whereNotNull($field);
        })->get()->map(function ($store) use ($field) {
            return [
                'id' => $store->id,
                'value' => $field ? $store->$field : $store->id,
                'label' => $field ? $store->$field : $store->jbs_name,
            ];
        })->unique('value')->values()->toArray();
    }

    public function getActivityDataTable($filters = [], $orders = [], $perPage = 10): LengthAwarePaginator
    {
        $query = Activity::query()
            ->from('activity_log')
            ->leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->leftJoin('user_roles', 'user_roles.id', '=', 'users.user_role_id')
            ->select([
                'activity_log.*',
                'users.name as causer_name',
                'user_roles.type as causer_role',
                'activity_log.properties->title as title',
                'activity_log.properties->activity_type as activity_type',
            ])
            ->where('activity_log.subject_type', 'store')
            ->where('activity_log.subject_id', request()->route('store'))
            ->with('causer');

        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($filter['column'])) {
                    $columnName = $filter['column'];
                    $operator = $filter['operator'];
                    $value = $filter['value'];

                    $query->where($columnName, $operator, $value);
                }
            }
        }

        $sortColumn = 'activity_log.updated_at';
        if ($orders) {
            foreach ($orders as $column => $data) {
                $sortColumn = $data['column'];
                if ($data['value'] == 'desc') {
                    $sortColumn = '-'.$data['column']; // Hyphen on front means descending
                }
            }
        }

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::custom(
                    'search',
                    new FuzzyFilter(
                        'activity_log.subject_type',
                        'activity_log.description',
                        'activity_log.created_at',
                        'users.name',
                    )
                ),
            ])
            ->defaultSort($sortColumn)
            ->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'description' => $item->description,
                'title' => $item->title,
                'activity_type' => $item->activity_type,
                'user_name' => $item->causer_name,
                'causer_role' => $item->causer_role,
                'formatted_date' => DateHelper::changeDateTimeFormat($item->created_at, 'M d, Y'),
                'formatted_time' => DateHelper::changeDateTimeFormat($item->created_at, 'h:i A'),
                'profile_photo_url' => optional($item->causer)->profile_photo_url,
            ];
        });

        return $data;
    }

    // Used in dashboard stats
    public function getStoreOpeningClosures(array $filters)
    {
        $storeTypes = ['Branch', 'Express', 'Junior', 'Outlet'];
        $dateField = $filters['date_field'] ?? 'grand_opening_date';
        if (! in_array($dateField, ['grand_opening_date', 'maintenance_permanent_closure_date'])) {
            throw new InvalidArgumentException('Invalid date field provided.');
        }
        $year = $filters['date_year'] ?? now()->year;
        $region = $filters['region'] ?? 'LUZ';
        $storeGroup = $filters['store_group'] ?? 'FullFranchise';

        // Define the start and end of the selected year
        $dateFrom = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $dateTo = Carbon::createFromDate($year, 12, 31)->endOfDay();

        // Build the default months array with 0 counts
        $result = [];

        $period = CarbonPeriod::create($dateFrom, '1 month', $dateTo);

        foreach ($period as $month) {
            $monthName = $month->format('F');

            $result[$monthName] = [
                'Branch' => 0,
                'Express' => 0,
                'Junior' => 0,
                'Outlet' => 0,
                'Others' => 0,
                'total' => 0,
            ];
        }

        $storeGroupValues = match ($storeGroup) {
            'FullFranchise' => [StoreGroupEnum::FranchiseeFZE()->value],
            'CompanyOwned' => [
                StoreGroupEnum::CompanyOwnedJFC()->value,
                StoreGroupEnum::CompanyOwnedBGC()->value,
            ],
            default => [],
        };

        // Fetch stores matching region and year
        $stores = Store::query()
            ->select('store_type', 'region', 'store_group', $dateField)
            ->when(! empty($storeGroupValues), function ($query) use ($storeGroupValues) {
                $query->whereIn('store_group', $storeGroupValues);
            })
            ->where('region', $region)
            ->whereYear($dateField, $year)
            ->whereNotNull($dateField)
            ->get();

        foreach ($stores as $store) {
            $monthName = Carbon::parse($store->{$dateField})->format('F');
            $type = in_array($store->store_type, $storeTypes) ? $store->store_type : 'Others';

            if (isset($result[$monthName])) {
                $result[$monthName][$type]++;
                $result[$monthName]['total']++;
            }
        }

        // Build the yearly totals
        $yearTotal = [
            'Branch' => 0,
            'Express' => 0,
            'Junior' => 0,
            'Outlet' => 0,
            'Others' => 0,
            'total' => 0,
        ];

        foreach ($result as $monthData) {
            foreach ($monthData as $type => $count) {
                if (! isset($yearTotal[$type])) {
                    $yearTotal[$type] = 0;
                }
                $yearTotal[$type] += $count;
            }
        }
        $result = ['year_total' => $yearTotal] + $result;

        return $result;
    }

    public function getStoreTemporaryClosures(array $filters)
    {
        $storeTypes = ['Branch', 'Express', 'Junior', 'Outlet'];

        $year = $filters['date_year'] ?? now()->year;
        $region = $filters['region'] ?? 'LUZ';
        $storeGroup = $filters['store_group'] ?? 'FullFranchise';

        $dateField = 'maintenance_temporary_closed_at';

        // Define the start and end of the selected year
        $dateFrom = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $dateTo = Carbon::createFromDate($year, 12, 31)->endOfDay();

        // Build the default months array with zero counts
        $result = [];

        $period = CarbonPeriod::create($dateFrom, '1 month', $dateTo);

        foreach ($period as $month) {
            $monthName = $month->format('F');

            $result[$monthName] = [
                'Branch' => 0,
                'Express' => 0,
                'Junior' => 0,
                'Outlet' => 0,
                'Others' => 0,
                'total' => 0,
            ];
        }

        $storeGroupValues = match ($storeGroup) {
            'FullFranchise' => [StoreGroupEnum::FranchiseeFZE()->value],
            'CompanyOwned' => [
                StoreGroupEnum::CompanyOwnedJFC()->value,
                StoreGroupEnum::CompanyOwnedBGC()->value,
            ],
            default => [],
        };

        // Fetch stores with filters
        $stores = Store::query()
            ->select('store_type', 'region', 'store_group', $dateField)
            ->when(! empty($storeGroupValues), function ($query) use ($storeGroupValues) {
                $query->whereIn('store_group', $storeGroupValues);
            })
            ->where('region', $region)
            ->whereYear($dateField, $year)
            ->whereNotNull($dateField)
            ->get();

        foreach ($stores as $store) {
            $monthName = Carbon::parse($store->{$dateField})->format('F');
            $type = in_array($store->store_type, $storeTypes) ? $store->store_type : 'Others';

            if (isset($result[$monthName])) {
                $result[$monthName][$type]++;
                $result[$monthName]['total']++;
            }
        }

        // Build the yearly totals
        $yearTotal = [
            'Branch' => 0,
            'Express' => 0,
            'Junior' => 0,
            'Outlet' => 0,
            'Others' => 0,
            'total' => 0,
        ];

        foreach ($result as $monthData) {
            foreach ($monthData as $type => $count) {
                if (! isset($yearTotal[$type])) {
                    $yearTotal[$type] = 0;
                }
                $yearTotal[$type] += $count;
            }
        }
        $result = ['year_total' => $yearTotal] + $result;

        return $result;
    }

    protected function generateStoreCode(): string
    {
        $latest = Store::whereNotNull('store_code')
            ->orderByDesc('id')
            ->first();

        if ($latest && preg_match('/^B(\d+)$/', $latest->store_code, $matches)) {
            $lastNumber = (int) $matches[1];
            $nextNumber = max($lastNumber + 1, 30000);
        } else {
            $nextNumber = 30000;
        }

        return 'B'.$nextNumber;
    }
}
