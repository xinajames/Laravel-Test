<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Document;
use App\Models\Franchisee;
use App\Models\Store;
use App\Support\Filters\FuzzyFilter;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;
use App\Traits\ManageFilesystems;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DocumentService
{
    use HandleTransactions;
    use ManageActivities;
    use ManageFilesystems;

    public function getDataTable($filters = [], $orders = [], $perPage = 10, $requestPayload = []): LengthAwarePaginator
    {
        $user = auth()->user();
        $type = [];
        if ($user?->hasPermissionTo('read-stores') || $user?->hasPermissionTo('update-stores')) {
            $type[] = 'store';
        }

        if ($user?->hasPermissionTo('read-franchisees') || $user?->hasPermissionTo('update-franchisees')) {
            $type[] = 'franchisee';
        }

        $query = Document::query()
            ->with(['documentable', 'createdBy', 'updatedBy'])
            ->whereNull('documents.deleted_at')
            ->whereIn('documentable_type', $type)
            ->whereHas('documentable', function ($q) {
                $q->where('is_draft', false);
            });

        // Join the store table if filtering by jbs_name
        if (in_array('store', $type)) {
            $query->leftJoin('stores', function ($join) {
                $join->on('documents.documentable_id', '=', 'stores.id')
                    ->where('documents.documentable_type', 'store');
            });
        }

        // Join the franchisees if filtering by name
        if (in_array('franchisee', $type)) {
            $query->leftJoin('franchisees', function ($join) {
                $join->on('documents.documentable_id', '=', 'franchisees.id')
                    ->where('documents.documentable_type', 'franchisee');
            });
        }

        $query->select('documents.*');

        if (! empty($requestPayload['model']) && ! empty($requestPayload['id'])) {
            $modelMap = [
                'store' => Store::class,
                'franchisee' => Franchisee::class,
            ];

            $modelClass = $modelMap[$requestPayload['model']];
            $modelId = $requestPayload['id'];

            if ($modelClass::where('id', $modelId)->exists()) {
                $query->where('documentable_type', $requestPayload['model'])
                    ->where('documentable_id', $modelId);
            } else {
                $query->whereRaw('1 = 0'); // ID not found
            }
        }

        if ($filters) {
            foreach ($filters as $filter) {
                $column = $filter['column'];
                $operator = $filter['operator'];
                $raw = $filter['value'];
                // treat any *_at column as a date
                if (Str::endsWith($column, '_at')) {
                    // decide which format we got
                    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $raw)) { // 04-23-2025
                        $date = Carbon::createFromFormat('m-d-Y', $raw);
                    } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) { // 04/23/2025
                        $date = Carbon::createFromFormat('m/d/Y', $raw);
                    }
                } else {
                    $date = Carbon::parse($raw);
                }

                $query->whereDate(
                    $column,
                    $operator,
                    $operator === '<=' ? $date->endOfDay() : $date->startOfDay()
                );
            }

            $query->where($column, $operator, $raw);
        }

        $sortColumn = 'documents.updated_at';
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
                        'document_name',
                        'file_path',
                        'file_type',
                        'stores.jbs_name',
                        'franchisees.first_name',
                        'franchisees.middle_name',
                        'franchisees.last_name',
                        'document_franchisee_name',
                    )
                ),
            ])
            ->defaultSort($sortColumn)
            ->paginate($perPage);

        $formattedData = $data->getCollection()->map(function ($data) {
            $data->formatted_created_at = DateHelper::changeDateTimeFormat($data->created_at);
            $data->formatted_updated_at = DateHelper::changeDateTimeFormat($data->updated_at);
            $data->preview = $this->retrieveFile($data->file_path);
            $data->download_url = route('documents.download', $data->id);

            $data->documentable_type = Str::ucfirst(class_basename($data->documentable_type));
            if ($data->documentable_type === 'Store' && $data->documentable) {
                $data->documentable_name = $data->documentable->jbs_name;
                $data->documentable_link = route(
                    'stores.show',
                    ['store' => $data->documentable->id, 'tab' => 'Documents']
                );
            } elseif ($data->documentable_type === 'Franchisee' && $data->documentable) {
                $data->documentable_name = $data->documentable->full_name;
                $data->documentable_link = route(
                    'franchisees.show',
                    ['franchisee' => $data->documentable->id, 'tab' => 'Documents']
                );
            } else {
                $data->documentable_name = '-';
                $data->documentable_link = '#';
            }

            return $data;
        });

        $data->setCollection($formattedData);

        return $data;
    }

    public function store($documents, string $model, int $id, $user = null)
    {
        $user = $user ?? auth()->user();
        $basePath = $this->generateUploadBasePath();

        $modelMap = [
            'store' => Store::class,
            'franchisee' => Franchisee::class,
        ];

        if (! isset($modelMap[$model])) {
            throw new InvalidArgumentException("Invalid model type: {$model}");
        }

        $modelClass = $modelMap[$model];
        $instance = $modelClass::findOrFail($id);

        return $this->transact(function () use ($documents, $model, $instance, $user, $basePath) {
            if ($documents && is_array($documents)) {
                foreach ($documents as $uploadedFile) {
                    $originalName = $uploadedFile->getClientOriginalName();
                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                    $storageFolder = "{$basePath}/{$model}/{$instance->id}/documents";
                    $fileName = $originalName;
                    $counter = 1;

                    while (Storage::exists("{$storageFolder}/{$fileName}")) {
                        $fileName = "{$nameOnly}_{$counter}.{$extension}";
                        $counter++;
                    }

                    $fullPath = "{$storageFolder}/{$fileName}";
                    $uploadDisk = $this->getDefaultUploadDisk();
                    $this->upload($uploadedFile, $fullPath, $uploadDisk);

                    $instance->documents()->create([
                        'document_name' => $fileName,
                        'file_name' => $fileName,
                        'file_path' => $fullPath,
                        'disk' => $uploadDisk,
                        'file_type' => $uploadedFile->getClientMimeType(),
                        'file_size' => $uploadedFile->getSize(),
                        'created_by_id' => $user->id,
                        'updated_by_id' => $user->id,
                    ]);
                }
            }

            $this->log($instance, 'documents.store', $user);
        });
    }

    public function delete(Document $document): bool
    {
        return $this->transact(function () use ($document) {
            if (! empty($document->file_path)) {
                $this->deleteFile($document->file_path, $document->disk);
            }

            $documentable = $document->documentable;

            $document->delete();

            if ($documentable) {
                $this->log($documentable, 'documents.delete');
            }

            return true;
        });
    }
}
