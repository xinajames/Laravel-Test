<?php

namespace App\Services;

use App\Enums\FranchiseeStatusEnum;
use App\Enums\StoreGroupEnum;
use App\Enums\StoreStatusEnum;
use App\Helpers\DateHelper;
use App\Models\Activity;
use App\Models\Franchisee;
use App\Models\Store;
use App\Support\Filters\FuzzyFilter;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;
use App\Traits\ManageFilesystems;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FranchiseeService
{
    use HandleTransactions;
    use ManageActivities;
    use ManageFilesystems;

    public function createApplication(array $franchiseeData = [])
    {
        return $this->transact(function () use ($franchiseeData) {
            return Franchisee::create($franchiseeData);
        });
    }

    public function delete(Franchisee $franchisee)
    {
        $this->transact(function () use ($franchisee) {
            $franchisee->delete();

            if (! $franchisee->is_draft) {
                $this->log($franchisee, 'franchisees.delete');
            }
        });
    }

    public function updateStatus(Franchisee $franchisee, $status, $logAction)
    {
        $this->transact(function () use ($franchisee, $status, $logAction) {
            $franchisee->update(['status' => $status]);

            if (! $franchisee->is_draft) {
                $this->log($franchisee, $logAction);
            }

            return $franchisee;
        });
    }

    public function update(array $franchiseeData, Franchisee $franchisee, $user = null)
    {
        $user = $user ?? auth()->user();

        $basePath = $this->generateUploadBasePath();

        $this->transact(function () use ($franchiseeData, $franchisee, $user, $basePath) {
            if (! empty($franchiseeData['profile_photo']) && is_file($franchiseeData['profile_photo'])) {
                if (! empty($franchisee->franchisee_profile_photo)) {
                    $this->deleteFile($franchisee->franchisee_profile_photo);
                }

                $originalName = $franchiseeData['profile_photo']->getClientOriginalName();
                $baseFileName = pathinfo($originalName, PATHINFO_FILENAME);
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $fileName = "{$baseFileName}.{$extension}";
                $franchiseeData['franchisee_profile_photo'] = "{$basePath}/franchisee/{$franchisee?->id}/photos/{$fileName}";

                $this->upload($franchiseeData['profile_photo'], $franchiseeData['franchisee_profile_photo']);
            }

            foreach ($this->getDateFields() as $field) {
                if (! empty($franchiseeData[$field])) {
                    $franchiseeData[$field] = Carbon::createFromFormat('m-d-Y', $franchiseeData[$field])
                        ->format('Y-m-d');
                }
            }

            if (isset($franchiseeData['background']) && $franchiseeData['background'] !== 'Others') {
                $franchiseeData['custom_background'] = null;
            }

            if (isset($franchiseeData['source_of_information']) && $franchiseeData['source_of_information'] !== 'Others') {
                $franchiseeData['custom_source_of_information'] = null;
            }

            if (isset($franchiseeData['generation']) && $franchiseeData['generation'] !== 'Others') {
                $franchiseeData['custom_generation'] = null;
            }

            $franchisee->update($franchiseeData);

            if (isset($franchiseeData['application_step']) && $franchiseeData['current_step'] == 'requirements') {
                foreach ($franchiseeData as $key => $documentGroup) {
                    if (Str::startsWith($key, 'documents_') && is_array($documentGroup)) {
                        foreach ($documentGroup as $file) {
                            if (isset($file['files']) && is_array($file['files'])) {
                                $basePath = $this->generateUploadBasePath();
                                $uploadedFiles = $file['files'];
                                foreach ($uploadedFiles as $uploadedFile) {
                                    $originalName = $uploadedFile->getClientOriginalName();
                                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                                    // Folder path: base/franchisee/{franchisee_id}/documents
                                    $storageFolder = "{$basePath}/franchisee/{$franchisee->id}/documents";
                                    $fileName = $originalName;
                                    $counter = 1;

                                    while (Storage::exists("{$storageFolder}/{$fileName}")) {
                                        $fileName = "{$nameOnly}_{$counter}.{$extension}";
                                        $counter++;
                                    }

                                    $fullPath = "{$storageFolder}/{$fileName}";
                                    $uploadDisk = $this->getDefaultUploadDisk();

                                    $this->upload($uploadedFile, $fullPath, $uploadDisk);

                                    $franchisee->documents()->create([
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

            if (! $franchisee->is_draft && ! array_key_exists('is_draft', $franchiseeData)) {
                $this->log($franchisee, 'franchisees.update', $user);
            } elseif (array_key_exists('is_draft', $franchiseeData) && $franchiseeData['is_draft'] === false) {
                // Generate app code, log when a franchisee is completely created
                if (! $franchisee->franchisee_code) {
                    $nextCode = $this->generateFranchiseeCode();
                    $franchisee->update(['franchisee_code' => $nextCode]);
                }
                $this->log($franchisee, 'franchisees.store', $user);
            }

            return $franchisee;
        });
    }

    public function getDataTable($filters = [], $orders = [], $perPage = 10)
    {
        $query = Franchisee::query()
            ->withCount('stores')
            ->addSelect(
                DB::raw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as franchisee_name"),
                DB::raw(
                    "CONCAT(
                        COALESCE(residential_address_street, ''), ', ',
                        COALESCE(residential_address_barangay, ''), ', ',
                        COALESCE(residential_address_city, ''), ', ',
                        COALESCE(residential_address_province, ''), ', ',
                        COALESCE(residential_address_postal, '')) as full_residential_address"
                )
            )
            ->where('is_draft', false)
            // ->where('status', FranchiseeStatusEnum::Active()->value)
            ->whereNull('deleted_at');

        $hasMissingFieldsFilter = false;
        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($filter['column']) && $filter['column'] === 'has_missing_fields') {
                    $hasMissingFieldsFilter = true;
                } elseif (isset($filter['column'])) {
                    $columnName = $filter['column'];
                    $operator = $filter['operator'];
                    $value = $filter['value'];
                    $query->where($columnName, $operator, $value);
                }
            }
        }

        // If filtering by has_missing_fields, fetch all, filter and paginate in PHP
        if ($hasMissingFieldsFilter) {
            $franchisees = $query->get();
            $mapped = $franchisees->map(function ($data) {
                $missingFields = $this->hasMissingFields($data);
                $data->missing_fields = $missingFields['missing_fields'];
                $data->missing_field_labels = $missingFields['missing_field_labels'];
                $data->has_missing_fields = $missingFields['has_missing_fields'];
                $data->statusLabel = FranchiseeStatusEnum::getDescription($data->status);
                $data->statusType = ($data->status == FranchiseeStatusEnum::Active()->value) ? 'Active' : 'Inactive';
                $data->formatted_created_at = DateHelper::changeDateTimeFormat($data->created_at);
                $data->formatted_updated_at = DateHelper::changeDateTimeFormat($data->updated_at);

                return $data;
            });
            // Apply the has_missing_fields filter in PHP
            foreach ($filters as $filter) {
                if (isset($filter['column']) && $filter['column'] === 'has_missing_fields') {
                    $mapped = $mapped->filter(function ($item) use ($filter) {
                        return $item->has_missing_fields == $filter['value'];
                    });
                }
            }
            $mapped = $mapped->values();
            // Paginate the filtered collection
            $page = request('page', 1);
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $mapped->forPage($page, $perPage)->values(),
                $mapped->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return $paginated;
        }

        // Otherwise, use normal DB pagination and mapping
        $sortColumn = 'franchisees.updated_at';
        if ($orders) {
            foreach ($orders as $column => $data) {
                $sortColumn = $data['column'];
                if ($data['value'] == 'desc') {
                    $sortColumn = '-'.$data['column'];
                }
            }
        }

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::custom(
                    'search',
                    new FuzzyFilter(
                        'first_name',
                        'middle_name',
                        'last_name',
                        'corporation_name',
                        'email',
                        'contact_number',
                        'franchisee_code',
                        'fm_region',
                    )
                ),
            ])
            ->defaultSort($sortColumn)
            ->paginate($perPage);

        $formattedData = $data->getCollection()->map(function ($data) {
            $missingFields = $this->hasMissingFields($data);
            $data->missing_fields = $missingFields['missing_fields'];
            $data->missing_field_labels = $missingFields['missing_field_labels'];
            $data->has_missing_fields = $missingFields['has_missing_fields'];

            $data->statusLabel = FranchiseeStatusEnum::getDescription($data->status);
            $data->statusType = ($data->status == FranchiseeStatusEnum::Active()->value) ? 'Active' : 'Inactive';

            $data->formatted_created_at = DateHelper::changeDateTimeFormat($data->created_at);
            $data->formatted_updated_at = DateHelper::changeDateTimeFormat($data->updated_at);

            return $data;
        });

        $data->setCollection($formattedData);

        return $data;
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
            ->where('activity_log.subject_type', 'franchisee')
            ->where('activity_log.subject_id', request()->route('franchisee'))
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

    public function getInformation(Franchisee $franchisee)
    {
        $franchisee->franchisee_profile_photo = $franchisee->franchisee_profile_photo
            ? $this->retrieveFile($franchisee->franchisee_profile_photo)
            : $franchisee->franchisee_profile_photo_url;
        $franchisee->status_description = FranchiseeStatusEnum::getDescription($franchisee->status);

        foreach ($this->getDateFields() as $field) {
            if (! empty($franchisee->$field)) {
                $franchisee->$field = Carbon::parse($franchisee->$field)->format('m-d-Y');
            }
        }

        $franchisee->stores = $franchisee->stores ?? collect();
        $franchisee->documents = $franchisee->documents ?? collect();
        $franchisee->activities_count = 0;

        $franchisee->recent_documents = $franchisee->documents()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($document) {
                $document->formatted_created_at = DateHelper::changeDateTimeFormat($document->created_at);
                $document->preview = $this->retrieveFile($document->file_path);

                return $document;
            });

        $franchisee->loadMissing('stores');

        $franchisee->stores = $franchisee->stores->map(function ($store) {
            $photo = $store->photos()
                ->orderBy('created_at', 'asc')
                ->limit(1)
                ->first();
            $store->image = $photo
                ? $this->retrieveFile($photo->file_path)
                : null;

            $store->recent_rating = $store->storeRatings()
                ->where('is_draft', false)
                ->orderBy('updated_at', 'desc')
                ->first();

            return $store;
        });

        return $franchisee;
    }

    public function getDateFields()
    {
        return [
            'birthdate',
            'wedding_date',
            'spouse_birthdate',
            'date_start_bakery_management_seminar',
            'date_end_bakery_management_seminar',
            'date_start_bread_baking_course',
            'date_end_bread_baking_course',
            'date_applied',
            'date_approved',
            'date_separated',
        ];
    }

    public function getQuickInformation(Franchisee $franchisee)
    {
        return [
            'franchisee_name' => $franchisee->full_name,
            'franchisee_code' => $franchisee->franchisee_code,
            'corporation_name' => $franchisee->corporation_name,
        ];
    }

    public function getDataList($field = null): array
    {
        return Franchisee::all()->map(function ($franchisee) use ($field) {
            $franchiseeName = $franchisee->corporation_name
                ? "{$franchisee->full_name} - {$franchisee->corporation_name}" : $franchisee->full_name;

            return [
                'id' => $franchisee->id,
                'value' => $franchisee->id,
                'label' => $field ? $franchisee->$field : $franchiseeName,
                'image' => $franchisee->franchisee_profile_photo
                    ? $this->retrieveFile($franchisee->franchisee_profile_photo)
                    : null,
                'franchisee_name' => $franchisee->full_name,
                'corporation_name' => $franchisee->corporation_name,
                'franchisee_code' => $franchisee->franchisee_code,
            ];
        })->unique('value')->values()->toArray();
    }

    // Used in dashboard stats
    public function getFranchiseeRegionDetails()
    {
        $franchiseesWithValidStores = Franchisee::query()
            ->whereHas('stores', function ($query) {
                $query->whereIn('store_status', [
                    StoreStatusEnum::Open(),
                    StoreStatusEnum::TemporaryClosed(),
                ]);
            })
            ->where('is_draft', false)
            ->where('status', FranchiseeStatusEnum::Active()->value)
            ->get(['fm_region']);

        $regionCounts = $franchiseesWithValidStores
            ->groupBy(function ($franchisee) {
                return $franchisee->fm_region ?? 'NULL';
            })
            ->map(function ($group) {
                return $group->count();
            });

        $totalFranchisees = Franchisee::where('status', FranchiseeStatusEnum::Active()->value)->count();

        return [
            'LUZ' => $regionCounts->get('LUZ', 0),
            'VIS' => $regionCounts->get('VIS', 0),
            'MIN' => $regionCounts->get('MIN', 0),
            'NULL' => $regionCounts->get('NULL', 0),
            'TOTAL' => $totalFranchisees,
        ];
    }

    // Used in dashboard stats
    public function getFranchiseeCountDetails()
    {
        $storeTypes = ['Branch', 'Express', 'Junior', 'Outlet'];
        $regions = ['LUZ', 'VIS', 'MIN', 'NULL'];

        // Helper to group by region then store type
        $groupAndCount = function ($stores) use ($storeTypes, $regions) {
            $result = [];
            foreach ($regions as $region) {
                $result[$region] = [
                    'Branch' => 0,
                    'Express' => 0,
                    'Junior' => 0,
                    'Outlet' => 0,
                    'Others' => 0,
                    'total' => 0,
                ];
            }

            foreach ($stores as $store) {
                $region = $store->region ?? 'NULL';
                $region = in_array($region, ['LUZ', 'VIS', 'MIN']) ? $region : 'NULL';

                $storeType = $store->store_type;
                $typeKey = in_array($storeType, $storeTypes) ? $storeType : 'Others';

                if (! isset($result[$region])) {
                    $result[$region] = [
                        'Branch' => 0,
                        'Express' => 0,
                        'Junior' => 0,
                        'Outlet' => 0,
                        'Others' => 0,
                        'total' => 0,
                    ];
                }

                $result[$region][$typeKey]++;
                $result[$region]['total']++;
            }

            return $result;
        };

        // Fetch all relevant stores
        $allStores = Store::whereIn('store_status', [
            StoreStatusEnum::Open(),
            StoreStatusEnum::TemporaryClosed(),
        ])
            ->select('store_type', 'region')
            ->get();

        // FranchiseeStores (FullFranchise)
        $franchiseeStores = Store::query()
            ->where('store_group', StoreGroupEnum::FranchiseeFZE()->value)
            ->whereIn('store_status', [
                StoreStatusEnum::Open(),
                StoreStatusEnum::TemporaryClosed(),
            ])
            ->select('store_type', 'region')
            ->get();
        // CompanyOwned Stores (CompanyOwned)
        $companyOwnedStores = Store::query()
            ->whereIn(
                'store_group',
                [StoreGroupEnum::CompanyOwnedBGC()->value, StoreGroupEnum::CompanyOwnedJFC()->value]
            )
            ->select('store_type', 'region')
            ->whereIn('store_status', [
                StoreStatusEnum::Open(),
                StoreStatusEnum::TemporaryClosed(),
            ])
            ->get();

        return [
            'store' => $groupAndCount($allStores),
            'franchisee_stores' => $groupAndCount($franchiseeStores),
            'company_owned_stores' => $groupAndCount($companyOwnedStores),
        ];
    }

    protected function generateFranchiseeCode(): string
    {
        $latest = Franchisee::whereNotNull('franchisee_code')
            ->where('is_draft', false)
            ->orderByDesc('franchisee_code')
            ->first();

        if ($latest && preg_match('/^F(\d+)$/', $latest->franchisee_code, $matches)) {
            $lastNumber = (int) $matches[1];
            $nextNumber = max($lastNumber + 1, 20000);
        } else {
            $nextNumber = 20000;
        }

        $generatedCode = 'F'.$nextNumber;

        // Verify the generated code is not null or empty
        if (empty($generatedCode) || $generatedCode === 'F' || $nextNumber <= 0) {
            throw new \Exception('Failed to generate valid franchisee code');
        }

        // Verify uniqueness before returning
        $exists = Franchisee::where('franchisee_code', $generatedCode)->exists();
        if ($exists) {
            throw new \Exception("Franchisee code {$generatedCode} already exists");
        }

        return $generatedCode;
    }

    /**
     * Check if a Franchisee has missing required fields
     *
     * @return array Array containing missing field names and a boolean indicating if any fields are missing
     */
    public function hasMissingFields(Franchisee $franchisee): array
    {
        $missingFields = [];

        // Field name mapping for user-friendly messages
        $fieldMap = [
            'franchisee_profile_photo' => 'Profile Photo',
            'franchisee_code' => 'Franchisee Code',
            'last_name' => 'Last Name',
            'first_name' => 'First Name',
            'status' => 'Status',
            'tin' => 'TIN',
            'fm_district_manager' => 'District Manager',
            'fm_region' => 'Region',
            'date_start_bakery_management_seminar' => 'Bakery Management Seminar Start Date',
            'date_end_bakery_management_seminar' => 'Bakery Management Seminar End Date',
            'date_start_bread_baking_course' => 'Bread Baking Course Start Date',
            'date_end_bread_baking_course' => 'Bread Baking Course End Date',
            'operations_manual_number' => 'Operations Manual Number',
            'operations_manual_release' => 'Operations Manual Release',
            'date_applied' => 'Application Date',
            'date_approved' => 'Approval Date',
            'contact_number_required' => 'At least one Contact Number',
            'email_required' => 'At least one Email Address',
        ];

        // Define required fields here - add new fields to this array
        $requiredFields = [
            'franchisee_profile_photo',
            'franchisee_code',
            'last_name',
            'first_name',
            'status',
            'tin',
            'fm_district_manager',
            'fm_region',
            'date_start_bakery_management_seminar',
            'date_end_bakery_management_seminar',
            'date_start_bread_baking_course',
            'date_end_bread_baking_course',
            'operations_manual_number',
            'operations_manual_release',
            'date_applied',
            'date_approved',
        ];

        // Check each required field
        foreach ($requiredFields as $field) {
            if (empty($franchisee->$field)) {
                $missingFields[] = $field;
            }
        }

        // Check if at least one contact number is provided
        if (empty($franchisee->contact_number) &&
            empty($franchisee->contact_number_2) &&
            empty($franchisee->contact_number_3)) {
            $missingFields[] = 'contact_number_required';
        }

        // Check if at least one email is provided
        if (empty($franchisee->email) &&
            empty($franchisee->email_2) &&
            empty($franchisee->email_3)) {
            $missingFields[] = 'email_required';
        }

        // Map the technical field names to user-friendly names
        $missingFieldsLabels = array_map(function ($field) use ($fieldMap) {
            return $fieldMap[$field] ?? $field;
        }, $missingFields);

        return [
            'missing_fields' => $missingFields, // Original field names
            'missing_field_labels' => $missingFieldsLabels, // User-friendly field names
            'has_missing_fields' => count($missingFields) > 0,
        ];
    }
}
