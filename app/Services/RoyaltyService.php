<?php

namespace App\Services;

use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Enums\UserStatusEnum;
use App\Helpers\DateHelper;
use App\Models\Royalty\MacroBatch;
use App\Models\User;
use App\Support\Filters\FuzzyFilter;
use App\Traits\ManageActivities;
use App\Traits\ManageFilesystems;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoyaltyService
{
    use ManageActivities;
    use ManageFilesystems;

    public function __construct() {}

    public function getDataTable($filters = [], $orders = [], $perPage = 10)
    {
        $query = MacroBatch::query()
            ->select(
                'macro_batches.id',
                'macro_batches.code',
                'macro_batches.title',
                'macro_batches.remarks',
                'macro_batches.status',
                'macro_batches.errors',
                'macro_batches.user_id',
                'macro_batches.completed_at',
                'macro_batches.created_at',
                'macro_batches.updated_at',
            )
            ->with([
                'macroOutputs' => function ($subQuery) {
                    $subQuery->whereIn('file_revision_id', [
                        MacroFileRevisionEnum::MNSRAddedJBMISData()->value,
                        MacroFileRevisionEnum::MNSRCreatedRoyaltyData()->value,
                        MacroFileRevisionEnum::MNSRUpdatedRoyaltyData()->value,
                        MacroFileRevisionEnum::RoyaltyDefault()->value,
                        MacroFileRevisionEnum::RoyaltyUpdated()->value,
                        MacroFileRevisionEnum::JBSSalesHistoryDefault()->value,
                        MacroFileRevisionEnum::JBSSalesHistoryByStoreDefault()->value,
                    ]);
                },
                'macroUploads',
            ]);

        if (! empty($filters)) {
            foreach ($filters as $filter) {
                if (isset($filter['column'], $filter['operator'], $filter['value'])) {
                    $query->where($filter['column'], $filter['operator'], $filter['value']);
                }
            }
        }

        $defaultSort = 'macro_batches.created_at';
        if (! empty($orders)) {
            foreach ($orders as $order) {
                if (isset($order['column'], $order['value'])) {
                    $query->orderBy($order['column'], $order['value']);
                }
            }
        } else {
            $query->orderBy($defaultSort, 'desc');
        }

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::custom('search', new FuzzyFilter('title', 'remarks')),
            ])
            ->paginate($perPage)
            ->appends(request()->query());

        $data->getCollection()->transform(function ($item) {
            $item->status_label = UserStatusEnum::getDescription($item->status);
            $item->formatted_created_at = DateHelper::changeDateTimeFormat($item->created_at);
            $item->formatted_updated_at = DateHelper::changeDateTimeFormat($item->updated_at);
            $item->user_name = User::where('id', $item->user_id)->value('name');

            $item->formatted_completed_date = $item->completed_at
                ? DateHelper::changeDateTimeFormat($item->completed_at, 'F d, Y')
                : null;

            $item->completed_date = $item->completed_at
                ? DateHelper::changeDateTimeFormat($item->completed_at, 'Y-m-d')
                : null;

            $item->formatted_completed_time = $item->completed_at
                ? DateHelper::changeDateTimeFormat($item->completed_at, 'H:i:s')
                : null;

            $outputs = $item->macroOutputs;

            $latestMnsr = $outputs
                ->filter(function ($output) {
                    return $output->file_type_id == MacroFileTypeEnum::MNSR()->value &&
                        in_array($output->file_revision_id, [
                            MacroFileRevisionEnum::MNSRAddedJBMISData()->value,
                            MacroFileRevisionEnum::MNSRCreatedRoyaltyData()->value,
                            MacroFileRevisionEnum::MNSRUpdatedRoyaltyData()->value,
                        ]);
                })
                ->sortByDesc('updated_at')
                ->first();

            $latestRoyalty = $outputs
                ->filter(function ($output) {
                    return $output->file_type_id == MacroFileTypeEnum::Royalty()->value &&
                        in_array($output->file_revision_id, [
                            MacroFileRevisionEnum::RoyaltyDefault()->value,
                            MacroFileRevisionEnum::RoyaltyUpdated()->value,
                        ]);
                })
                ->sortByDesc('updated_at')
                ->first();

            $latestJBSSalesHistory = $outputs
                ->filter(function ($output) {
                    return $output->file_type_id == MacroFileTypeEnum::JBSSalesHistory()->value &&
                        $output->file_revision_id == MacroFileRevisionEnum::JBSSalesHistoryDefault()->value;
                })
                ->sortByDesc('updated_at')
                ->first();

            $latestJBSSalesHistoryByStore = $outputs
                ->filter(function ($output) {
                    return $output->file_type_id == MacroFileTypeEnum::JBSSalesHistoryByStore()->value &&
                        $output->file_revision_id == MacroFileRevisionEnum::JBSSalesHistoryByStoreDefault()->value;
                })
                ->sortByDesc('updated_at')
                ->first();

            if ($latestMnsr) {
                $item->mnsr_file_name = $latestMnsr->file_name;
                $item->mnsr_file_id = $latestMnsr->id;
            }

            if ($latestRoyalty) {
                $item->royalty_file_name = $latestRoyalty->file_name;
                $item->royalty_file_id = $latestRoyalty->id;
            }

            if ($latestJBSSalesHistory) {
                $item->jbs_sales_history_file_name = $latestJBSSalesHistory->file_name;
                $item->jbs_sales_history_file_id = $latestJBSSalesHistory->id;
            }

            if ($latestJBSSalesHistoryByStore) {
                $item->jbs_sales_history_by_store_file_name = $latestJBSSalesHistoryByStore->file_name;
                $item->jbs_sales_history_by_store_file_id = $latestJBSSalesHistoryByStore->id;
            }

            $item->setRelation('macroOutputs', collect([$latestMnsr, $latestRoyalty, $latestJBSSalesHistory, $latestJBSSalesHistoryByStore])->filter());

            return $item;
        });

        return $data;
    }
}
